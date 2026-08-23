<#
.SYNOPSIS
    Avaye Print Bridge - local print agent for Windows.

.DESCRIPTION
    A tiny, dependency-free HTTP agent that runs on the operator's own PC.
    It lets the web panel (which may be served from a shared host / main
    domain) detect the Windows printers installed on THIS machine and send
    raw label data (ZPL/TSPL) directly to them.

    - Listens ONLY on http://127.0.0.1:<Port> (default 9235), so it is not
      reachable from the network.
    - No installation and no admin rights required: it uses a plain TCP
      listener instead of HttpListener/http.sys.
    - Optional shared token: create a token.txt file next to this script;
      its trimmed content becomes the required X-Bridge-Token header.
#>

param(
    [int]$Port = 9235
)

$ErrorActionPreference = 'Stop'
$script:Version = '1.0.0'
$script:MaxBodyBytes = 16MB

try {
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
} catch {
}

# ---------------------------------------------------------------------------
# Winspool raw printing (datatype = RAW) so ZPL/TSPL streams reach the
# printer driver untouched. Compiled locally with Add-Type, no dependencies.
# ---------------------------------------------------------------------------
if (-not ('Avaye.RawPrinter' -as [type])) {
    Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;

namespace Avaye
{
    public static class RawPrinter
    {
        [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
        public struct DOC_INFO_1
        {
            public string pDocName;
            public string pOutputFile;
            public string pDatatype;
        }

        [DllImport("winspool.Drv", SetLastError = true, CharSet = CharSet.Ansi)]
        private static extern bool OpenPrinter(string szPrinter, out IntPtr hPrinter, IntPtr pd);

        [DllImport("winspool.Drv", SetLastError = true)]
        private static extern bool ClosePrinter(IntPtr hPrinter);

        [DllImport("winspool.Drv", SetLastError = true, CharSet = CharSet.Ansi)]
        private static extern bool StartDocPrinter(IntPtr hPrinter, int level, [In] ref DOC_INFO_1 di);

        [DllImport("winspool.Drv", SetLastError = true)]
        private static extern bool EndDocPrinter(IntPtr hPrinter);

        [DllImport("winspool.Drv", SetLastError = true)]
        private static extern bool StartPagePrinter(IntPtr hPrinter);

        [DllImport("winspool.Drv", SetLastError = true)]
        private static extern bool EndPagePrinter(IntPtr hPrinter);

        [DllImport("winspool.Drv", SetLastError = true)]
        private static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, int dwCount, out int dwWritten);

        public static void SendBytes(string printerName, byte[] data, string jobName)
        {
            IntPtr hPrinter;
            if (!OpenPrinter(printerName, out hPrinter, IntPtr.Zero))
            {
                throw new Exception("OpenPrinter failed (Win32 error " + Marshal.GetLastWin32Error() + "). Printer name may be wrong.");
            }

            try
            {
                DOC_INFO_1 docInfo = new DOC_INFO_1();
                docInfo.pDocName = string.IsNullOrEmpty(jobName) ? "Avaye Print Job" : jobName;
                docInfo.pDatatype = "RAW";
                docInfo.pOutputFile = null;

                if (!StartDocPrinter(hPrinter, 1, ref docInfo))
                    throw new Exception("StartDocPrinter failed (Win32 error " + Marshal.GetLastWin32Error() + ").");

                try
                {
                    if (!StartPagePrinter(hPrinter))
                        throw new Exception("StartPagePrinter failed (Win32 error " + Marshal.GetLastWin32Error() + ").");

                    IntPtr ptr = Marshal.AllocHGlobal(data.Length);
                    try
                    {
                        Marshal.Copy(data, 0, ptr, data.Length);
                        int written;
                        if (!WritePrinter(hPrinter, ptr, data.Length, out written))
                            throw new Exception("WritePrinter failed (Win32 error " + Marshal.GetLastWin32Error() + ").");
                        if (written != data.Length)
                            throw new Exception("Incomplete write to printer (" + written + "/" + data.Length + " bytes).");
                    }
                    finally
                    {
                        Marshal.FreeHGlobal(ptr);
                    }

                    if (!EndPagePrinter(hPrinter))
                        throw new Exception("EndPagePrinter failed (Win32 error " + Marshal.GetLastWin32Error() + ").");
                }
                finally
                {
                    if (!EndDocPrinter(hPrinter))
                        throw new Exception("EndDocPrinter failed (Win32 error " + Marshal.GetLastWin32Error() + ").");
                }
            }
            finally
            {
                ClosePrinter(hPrinter);
            }
        }
    }
}
"@
}

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

function Read-BridgeToken {
    $tokenPath = Join-Path $PSScriptRoot 'token.txt'

    if (-not (Test-Path $tokenPath)) {
        return ''
    }

    $content = Get-Content -Path $tokenPath -Raw -ErrorAction SilentlyContinue

    if ($null -eq $content) {
        return ''
    }

    return $content.Trim([char]0xFEFF, ' ', "`r", "`n", "`t")
}

function Get-PrinterList {
    # Cached for a few seconds; also pre-warmed at startup because the first
    # CIM query on some machines takes longer than the browser fetch timeout.
    if ($null -eq $script:PrinterCache -or ((Get-Date) - $script:PrinterCacheAt).TotalSeconds -gt 10) {
        $list = @(Get-CimInstance -ClassName Win32_Printer |
            Sort-Object -Property Name |
            ForEach-Object {
                [ordered]@{
                    name       = [string]$_.Name
                    is_default = [bool]$_.Default
                }
            })

        $script:PrinterCache = $list
        $script:PrinterCacheAt = Get-Date
    }

    return $script:PrinterCache
}

function Find-HeaderEnd([byte[]]$data) {
    for ($i = 0; $i -le $data.Length - 4; $i++) {
        if ($data[$i] -eq 13 -and $data[$i + 1] -eq 10 -and $data[$i + 2] -eq 13 -and $data[$i + 3] -eq 10) {
            return $i
        }
    }

    return -1
}

function Read-ExactBytes([System.IO.Stream]$stream, [int]$count) {
    $result = New-Object byte[] $count
    $offset = 0

    while ($offset -lt $count) {
        $read = $stream.Read($result, $offset, $count - $offset)

        if ($read -le 0) {
            break
        }

        $offset += $read
    }

    if ($offset -eq $count) {
        return , $result
    }

    $trimmed = New-Object byte[] $offset
    [Array]::Copy($result, $trimmed, $offset)
    return , $trimmed
}

function Read-HttpRequest([System.Net.Sockets.NetworkStream]$stream) {
    $memory = New-Object System.IO.MemoryStream
    $buffer = New-Object byte[] 4096
    $headerEndIndex = -1

    while ($headerEndIndex -lt 0) {
        $read = $stream.Read($buffer, 0, $buffer.Length)

        if ($read -le 0) {
            break
        }

        $memory.Write($buffer, 0, $read)

        if ($memory.Length -gt 64KB) {
            throw 'Request headers too large.'
        }

        $all = $memory.ToArray()
        $headerEndIndex = Find-HeaderEnd $all
    }

    if ($headerEndIndex -lt 0) {
        return $null
    }

    $all = $memory.ToArray()
    $headText = [System.Text.Encoding]::ASCII.GetString($all, 0, $headerEndIndex)
    $bodySoFar = $all.Length - ($headerEndIndex + 4)

    $lines = $headText -split "`r`n"
    $requestLineParts = $lines[0] -split '\s+'

    if ($requestLineParts.Count -lt 2) {
        return $null
    }

    $headers = @{}
    for ($i = 1; $i -lt $lines.Count; $i++) {
        $sep = $lines[$i].IndexOf(':')

        if ($sep -gt 0) {
            $name = $lines[$i].Substring(0, $sep).Trim().ToLowerInvariant()
            $value = $lines[$i].Substring($sep + 1).Trim()
            $headers[$name] = $value
        }
    }

    $contentLength = 0
    if ($headers.ContainsKey('content-length')) {
        [void][int]::TryParse($headers['content-length'], [ref]$contentLength)
    }

    if ($contentLength -gt $script:MaxBodyBytes) {
        throw 'Request body too large.'
    }

    $bodyBytes = New-Object byte[] $contentLength

    if ($bodySoFar -gt 0 -and $contentLength -gt 0) {
        $copy = [Math]::Min($bodySoFar, $contentLength)
        [Array]::Copy($all, $headerEndIndex + 4, $bodyBytes, 0, $copy)
    }

    if ($contentLength -gt 0 -and $bodySoFar -lt $contentLength) {
        $remaining = Read-ExactBytes $stream ($contentLength - $bodySoFar)
        [Array]::Copy($remaining, 0, $bodyBytes, $bodySoFar, $remaining.Length)
    }

    return @{
        method  = $requestLineParts[0].ToUpperInvariant()
        path    = $requestLineParts[1]
        headers = $headers
        body    = $bodyBytes
    }
}

function Send-JsonResponse([System.IO.Stream]$stream, [int]$statusCode, [string]$statusText, [object]$payload) {
    # ConvertTo-Json may leave Persian characters unescaped, so the body is
    # written as UTF-8 (matching the declared charset) to survive intact.
    $json = ConvertTo-Json -InputObject $payload -Depth 4 -Compress
    $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($json)

    $headers =
        "HTTP/1.1 $statusCode $statusText`r`n" +
        "Content-Type: application/json; charset=utf-8`r`n" +
        "Content-Length: $($bodyBytes.Length)`r`n" +
        "Connection: close`r`n" +
        "Cache-Control: no-store`r`n" +
        "X-Content-Type-Options: nosniff`r`n" +
        "Access-Control-Allow-Origin: *`r`n" +
        "Access-Control-Allow-Methods: GET, POST, OPTIONS`r`n" +
        "Access-Control-Allow-Headers: Content-Type, X-Bridge-Token, Access-Control-Request-Private-Network`r`n" +
        "Access-Control-Allow-Private-Network: true`r`n" +
        "Access-Control-Max-Age: 600`r`n" +
        "`r`n"

    $headBytes = [System.Text.Encoding]::ASCII.GetBytes($headers)
    $stream.Write($headBytes, 0, $headBytes.Length)

    if ($bodyBytes.Length -gt 0) {
        $stream.Write($bodyBytes, 0, $bodyBytes.Length)
    }

    $stream.Flush()
}

function Send-NoContentResponse([System.IO.Stream]$stream) {
    $headers =
        "HTTP/1.1 204 No Content`r`n" +
        "Connection: close`r`n" +
        "Access-Control-Allow-Origin: *`r`n" +
        "Access-Control-Allow-Methods: GET, POST, OPTIONS`r`n" +
        "Access-Control-Allow-Headers: Content-Type, X-Bridge-Token, Access-Control-Request-Private-Network`r`n" +
        "Access-Control-Allow-Private-Network: true`r`n" +
        "Access-Control-Max-Age: 600`r`n" +
        "`r`n"

    $headBytes = [System.Text.Encoding]::ASCII.GetBytes($headers)
    $stream.Write($headBytes, 0, $headBytes.Length)
    $stream.Flush()
}

function Test-BridgeToken([hashtable]$requestHeaders) {
    if ($script:Token -eq '') {
        return $true
    }

    if ($requestHeaders.ContainsKey('x-bridge-token') -and
        [string]::Equals($requestHeaders['x-bridge-token'], $script:Token, [System.StringComparison]::Ordinal)) {
        return $true
    }

    return $false
}

function Get-RootExceptionMessage([System.Exception]$exception) {
    # .NET method calls from PowerShell arrive wrapped in a generic
    # MethodInvocationException; surface the underlying message instead.
    $current = $exception

    while ($null -ne $current.InnerException) {
        $current = $current.InnerException
    }

    return $current.Message
}

# ---------------------------------------------------------------------------
# Request routing
# ---------------------------------------------------------------------------

function Invoke-BridgeRequest([hashtable]$request) {
    if ($request.method -eq 'OPTIONS') {
        return @{ status = 204 }
    }

    $path = ($request.path -split '\?')[0]
    $path = $path.TrimEnd('/')

    if ($path -eq '') {
        $path = '/'
    }

    switch ("$($request.method) $path") {
        'GET /' {
            return @{
                status  = 200
                payload = @{
                    ok      = $true
                    version = $script:Version
                    agent   = 'avaye-print-bridge'
                }
            }
        }
        'GET /ping' {
            return @{
                status  = 200
                payload = @{
                    ok      = $true
                    version = $script:Version
                    agent   = 'avaye-print-bridge'
                }
            }
        }
        'GET /api/printers' {
            return @{
                status  = 200
                payload = @{
                    ok       = $true
                    printers = Get-PrinterList
                }
            }
        }
        'POST /api/print' {
            $bodyText = [System.Text.Encoding]::UTF8.GetString($request.body)

            try {
                $payloadData = $bodyText | ConvertFrom-Json
            } catch {
                return @{
                    status  = 400
                    payload = @{ ok = $false; message = 'داده JSON نامعتبر است.' }
                }
            }

            $dataBase64 = ('' + $payloadData.data_base64) -replace '\s', ''
            $printerName = '' + $payloadData.printer
            $jobName = '' + $payloadData.job_name

            if ([string]::IsNullOrWhiteSpace($printerName)) {
                return @{
                    status  = 400
                    payload = @{ ok = $false; message = 'نام پرینتر انتخاب نشده است.' }
                }
            }

            if ([string]::IsNullOrWhiteSpace($dataBase64)) {
                return @{
                    status  = 400
                    payload = @{ ok = $false; message = 'داده چاپ خالی است.' }
                }
            }

            try {
                $bytes = [Convert]::FromBase64String($dataBase64)
            } catch {
                return @{
                    status  = 400
                    payload = @{ ok = $false; message = 'داده Base64 نامعتبر است.' }
                }
            }

            if ([string]::IsNullOrWhiteSpace($jobName)) {
                $jobName = 'Avaye Client Card'
            }

            try {
                [Avaye.RawPrinter]::SendBytes($printerName, $bytes, $jobName)

                return @{
                    status  = 200
                    payload = @{
                        ok      = $true
                        printer = $printerName
                        bytes   = $bytes.Length
                    }
                }
            } catch {
                return @{
                    status  = 500
                    payload = @{ ok = $false; message = (Get-RootExceptionMessage $_.Exception) }
                }
            }
        }
        default {
            if ($path -in @('/', '/ping', '/api/printers', '/api/print')) {
                return @{
                    status  = 405
                    payload = @{ ok = $false; message = 'Method not allowed.' }
                }
            }

            return @{
                status  = 404
                payload = @{ ok = $false; message = 'Not found.' }
            }
        }
    }
}

# ---------------------------------------------------------------------------
# Server loop
# ---------------------------------------------------------------------------

$script:Token = Read-BridgeToken

# Pre-warm the printer cache so the first click in the panel is instant.
try {
    Get-PrinterList | Out-Null
} catch {
    Write-Host "[warn] Initial printer enumeration failed: $($_.Exception.Message)"
}

Write-Host '============================================='
Write-Host ' Avaye Print Bridge'
Write-Host " Version : $script:Version"
if ($script:Token -ne '') {
    Write-Host ' Token   : enabled (token.txt)'
} else {
    Write-Host ' Token   : disabled'
}
Write-Host " Address : http://127.0.0.1:$Port"
Write-Host ' This window must stay open while printing.'
Write-Host '============================================='

$listener = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Loopback, $Port)

try {
    $listener.Start()
} catch {
    Write-Host "[error] Could not bind port ${Port}: $($_.Exception.Message)"
    exit 1
}

while ($true) {
    $client = $null

    try {
        $client = $listener.AcceptTcpClient()
    } catch {
        break
    }

    try {
        $client.ReceiveTimeout = 15000
        $client.SendTimeout = 15000
        $stream = $client.GetStream()

        $request = Read-HttpRequest $stream

        if ($null -ne $request) {
            if (-not (Test-BridgeToken $request.headers)) {
                Send-JsonResponse $stream 401 'Unauthorized' @{
                    ok      = $false
                    message = 'توکن امنیتی نادرست است.'
                }
            } else {
                $response = Invoke-BridgeRequest $request
                $statusText = switch ($response.status) {
                    200 { 'OK' }
                    204 { 'No Content' }
                    400 { 'Bad Request' }
                    401 { 'Unauthorized' }
                    404 { 'Not Found' }
                    405 { 'Method Not Allowed' }
                    500 { 'Internal Server Error' }
                    default { 'OK' }
                }

                if ($response.status -eq 204) {
                    Send-NoContentResponse $stream
                } else {
                    Send-JsonResponse $stream $response.status $statusText $response.payload
                }
            }
        }
    } catch {
        Write-Host "[warn] Request failed: $($_.Exception.Message)"
    } finally {
        if ($null -ne $client) {
            $client.Close()
        }
    }
}

$listener.Stop()
