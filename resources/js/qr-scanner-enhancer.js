let zxingReady = null;
let zxingReader = null;

const zxingOptions = {
    formats: ['QRCode'],
    tryHarder: true,
    tryRotate: true,
    tryInvert: true,
    tryDownscale: false,
    tryDenoise: true,
    maxNumberOfSymbols: 1,
    binarizer: 'LocalAverage',
    textMode: 'Plain',
};

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function ensureCanvas(size = 640) {
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;

    return {
        canvas,
        context: canvas.getContext('2d', {
            alpha: false,
            desynchronized: true,
            willReadFrequently: true,
        }),
    };
}

function sourceRect(video, scale) {
    const width = video.videoWidth || video.clientWidth;
    const height = video.videoHeight || video.clientHeight;
    const size = Math.floor(Math.min(width, height) * scale);

    return {
        x: Math.floor((width - size) / 2),
        y: Math.floor((height - size) / 2),
        size,
    };
}

function drawRoi(video, scale, outputSize) {
    const targetSize = outputSize || clamp(Math.floor(Math.min(video.videoWidth, video.videoHeight) * 1.25), 480, 960);
    const { canvas, context } = ensureCanvas(targetSize);
    const rect = sourceRect(video, scale);

    context.imageSmoothingEnabled = false;
    context.drawImage(video, rect.x, rect.y, rect.size, rect.size, 0, 0, targetSize, targetSize);

    return context.getImageData(0, 0, targetSize, targetSize);
}

function cloneImageData(imageData) {
    return new ImageData(new Uint8ClampedArray(imageData.data), imageData.width, imageData.height);
}

function grayscale(imageData) {
    const copy = cloneImageData(imageData);
    const data = copy.data;

    for (let index = 0; index < data.length; index += 4) {
        const value = (data[index] * 0.299) + (data[index + 1] * 0.587) + (data[index + 2] * 0.114);
        data[index] = value;
        data[index + 1] = value;
        data[index + 2] = value;
    }

    return copy;
}

function highContrast(imageData) {
    const copy = grayscale(imageData);
    const data = copy.data;
    let min = 255;
    let max = 0;

    for (let index = 0; index < data.length; index += 4) {
        const value = data[index];
        min = Math.min(min, value);
        max = Math.max(max, value);
    }

    const range = Math.max(1, max - min);

    for (let index = 0; index < data.length; index += 4) {
        const value = clamp(((data[index] - min) * 255) / range, 0, 255);
        data[index] = value;
        data[index + 1] = value;
        data[index + 2] = value;
    }

    return copy;
}

function threshold(imageData) {
    const copy = highContrast(imageData);
    const data = copy.data;
    let total = 0;
    let count = 0;

    for (let index = 0; index < data.length; index += 4) {
        total += data[index];
        count++;
    }

    const cutoff = total / Math.max(1, count);

    for (let index = 0; index < data.length; index += 4) {
        const value = data[index] > cutoff ? 255 : 0;
        data[index] = value;
        data[index + 1] = value;
        data[index + 2] = value;
    }

    return copy;
}

function sharpen(imageData) {
    const copy = highContrast(imageData);
    const source = new Uint8ClampedArray(copy.data);
    const data = copy.data;
    const width = copy.width;
    const height = copy.height;

    for (let y = 1; y < height - 1; y++) {
        for (let x = 1; x < width - 1; x++) {
            const offset = (y * width + x) * 4;
            const value = (
                source[offset] * 5
                - source[offset - 4]
                - source[offset + 4]
                - source[offset - (width * 4)]
                - source[offset + (width * 4)]
            );
            const clamped = clamp(value, 0, 255);
            data[offset] = clamped;
            data[offset + 1] = clamped;
            data[offset + 2] = clamped;
        }
    }

    return copy;
}

function sharpnessScore(imageData) {
    const data = imageData.data;
    const width = imageData.width;
    const step = Math.max(4, Math.floor(width / 160));
    let score = 0;
    let samples = 0;

    for (let y = step; y < imageData.height - step; y += step) {
        for (let x = step; x < width - step; x += step) {
            const offset = (y * width + x) * 4;
            const horizontal = Math.abs(data[offset] - data[offset + (step * 4)]);
            const vertical = Math.abs(data[offset] - data[offset + (step * width * 4)]);
            score += horizontal + vertical;
            samples++;
        }
    }

    return samples ? score / samples : 0;
}

async function ensureZxing() {
    if (!zxingReady) {
        zxingReady = import('zxing-wasm/reader').then(async (reader) => {
            zxingReader = reader;
            await reader.prepareZXingModule();

            return reader;
        }).catch((error) => {
            zxingReady = null;
            zxingReader = null;
            throw error;
        });
    }

    return zxingReady;
}

async function decodeWithZxing(imageData) {
    const reader = zxingReader || await ensureZxing();

    const results = await reader.readBarcodes(imageData, zxingOptions);
    const match = results.find((result) => result?.text && !result.error);

    return match?.text?.trim() || '';
}

export function createEnhancedQrScanner() {
    let lastFallbackAt = 0;
    let fallbackRunning = false;

    return {
        async decodeVideo(video) {
            const now = Date.now();

            if (!video || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
                return '';
            }

            if (fallbackRunning || now - lastFallbackAt < 450) {
                return '';
            }

            fallbackRunning = true;
            lastFallbackAt = now;

            try {
                const frames = [0.56, 0.7, 0.9]
                    .map((scale) => drawRoi(video, scale))
                    .sort((a, b) => sharpnessScore(b) - sharpnessScore(a))
                    .slice(0, 2);

                for (const frame of frames) {
                    const variants = [
                        frame,
                        highContrast(frame),
                        sharpen(frame),
                        threshold(frame),
                    ];

                    for (const variant of variants) {
                        const decoded = await decodeWithZxing(variant);

                        if (decoded) {
                            return decoded;
                        }
                    }
                }
            } catch (error) {
                return '';
            } finally {
                fallbackRunning = false;
            }

            return '';
        },
    };
}
