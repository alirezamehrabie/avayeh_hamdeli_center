/**
 * Alpine component that renders a delivery-receipt as a downloadable PNG.
 *
 * The receipt is drawn directly on a <canvas> (no external screenshot library)
 * so it fully supports the Persian language, RTL layout and the app's custom
 * `iransans` font. The canvas height is measured from the content first, so the
 * exported image always contains every item at correct dimensions.
 */

const FONT_FAMILY = "'iransans', 'Tahoma', sans-serif";
const SCALE = 2; // Retina-quality export.

// Layout constants (in CSS pixels; multiplied by SCALE when drawing).
const WIDTH = 640;
const PAD = 32;
const LINE = 26;

const COLORS = {
    ink: '#0f172a',
    sub: '#64748b',
    muted: '#94a3b8',
    line: '#e2e8f0',
    accent: '#4f46e5',
    panel: '#f8fafc',
    headBg: '#f1f5f9',
    white: '#ffffff',
};

export function deliveryReceipt(payload) {
    return {
        receiptOpen: false,
        downloading: false,
        downloadError: '',
        receipt: payload,

        async downloadImage() {
            if (this.downloading) {
                return;
            }

            this.downloading = true;
            this.downloadError = '';

            try {
                // Make sure the custom Persian font is ready before rasterizing,
                // otherwise the canvas would fall back to a default face.
                if (document.fonts && document.fonts.ready) {
                    try {
                        await document.fonts.load(`700 16px ${FONT_FAMILY}`);
                        await document.fonts.load(`400 16px ${FONT_FAMILY}`);
                        await document.fonts.ready;
                    } catch (e) {
                        // Non-fatal: fall back to whatever is available.
                    }
                }

                const canvas = this.renderCanvas();
                const url = canvas.toDataURL('image/png');

                const link = document.createElement('a');
                link.href = url;
                link.download = this.fileName();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (error) {
                this.downloadError = 'ساخت تصویر رسید ناموفق بود. دوباره تلاش کنید.';
                // eslint-disable-next-line no-console
                console.error('delivery-receipt download failed', error);
            } finally {
                this.downloading = false;
            }
        },

        fileName() {
            const name = (this.receipt.recipientName || 'receipt')
                .toString()
                .trim()
                .replace(/\s+/g, '-')
                .replace(/[\\/:*?"<>|]+/g, '');

            return `رسید-${name || 'تحویل'}.png`;
        },

        renderCanvas() {
            const r = this.receipt;
            const info = this.infoRows();
            const items = Array.isArray(r.items) ? r.items : [];
            const unitTotals = Array.isArray(r.unitTotals) ? r.unitTotals : [];

            // ---- Measure required height ---------------------------------
            const infoRowsCount = Math.ceil(info.length / 2);
            let height = 0;
            height += PAD; // top padding
            height += 64; // header block
            height += 24; // gap
            height += 16 + infoRowsCount * 40 + 16; // info panel
            height += 28; // items title
            height += 40; // items table head
            height += Math.max(items.length, 1) * 38; // items rows
            height += 20; // gap
            height += 52; // totals row
            height += 40; // footer
            height += PAD; // bottom padding

            const canvas = document.createElement('canvas');
            canvas.width = WIDTH * SCALE;
            canvas.height = Math.ceil(height) * SCALE;
            const ctx = canvas.getContext('2d');
            ctx.scale(SCALE, SCALE);
            ctx.textBaseline = 'middle';
            ctx.direction = 'rtl';

            // Background.
            ctx.fillStyle = COLORS.white;
            ctx.fillRect(0, 0, WIDTH, height);

            const right = WIDTH - PAD; // RTL anchor
            const left = PAD;
            let y = PAD;

            // ---- Header --------------------------------------------------
            ctx.textAlign = 'right';
            ctx.fillStyle = COLORS.accent;
            ctx.font = `700 13px ${FONT_FAMILY}`;
            ctx.fillText('رسید تحویل خدمت', right, y + 10);

            ctx.fillStyle = COLORS.ink;
            ctx.font = `800 22px ${FONT_FAMILY}`;
            ctx.fillText(this.truncate(ctx, r.serviceName || 'خدمت', WIDTH - PAD * 2), right, y + 34);

            ctx.fillStyle = COLORS.sub;
            ctx.font = `400 12px ${FONT_FAMILY}`;
            ctx.fillText(`کد خدمت: ${r.serviceCode || '-'}`, right, y + 54);

            // Date on the left side of the header.
            ctx.textAlign = 'left';
            ctx.fillStyle = COLORS.sub;
            ctx.font = `700 12px ${FONT_FAMILY}`;
            ctx.fillText(`تاریخ: ${r.date || '-'}`, left, y + 34);

            y += 64;

            // Divider (dashed).
            y += 12;
            this.dashedLine(ctx, left, right, y);
            y += 12;

            // ---- Info panel ----------------------------------------------
            const panelTop = y;
            const panelHeight = 16 + infoRowsCount * 40 + 8;
            this.roundRect(ctx, left, panelTop, WIDTH - PAD * 2, panelHeight, 14);
            ctx.fillStyle = COLORS.panel;
            ctx.fill();
            ctx.strokeStyle = COLORS.line;
            ctx.lineWidth = 1;
            ctx.stroke();

            const colGap = 16;
            const colWidth = (WIDTH - PAD * 2 - colGap) / 2;
            let iy = panelTop + 16;
            ctx.textAlign = 'right';

            info.forEach((row, index) => {
                const col = index % 2; // 0 => right column, 1 => left column
                const anchor = col === 0 ? right : right - colWidth - colGap;

                ctx.fillStyle = COLORS.muted;
                ctx.font = `400 11px ${FONT_FAMILY}`;
                ctx.fillText(row.label, anchor, iy + 8);

                ctx.fillStyle = COLORS.ink;
                ctx.font = `700 14px ${FONT_FAMILY}`;
                ctx.fillText(this.truncate(ctx, row.value, colWidth), anchor, iy + 26);

                if (col === 1 || index === info.length - 1) {
                    iy += 40;
                }
            });

            y = panelTop + panelHeight + 20;

            // ---- Items title ---------------------------------------------
            ctx.textAlign = 'right';
            ctx.fillStyle = COLORS.ink;
            ctx.font = `800 14px ${FONT_FAMILY}`;
            ctx.fillText('اقلام تحویل‌شده', right, y + 6);
            y += 24;

            // ---- Items table ---------------------------------------------
            const tableTop = y;
            const catX = right; // category (right)
            const qtyX = left + 150; // quantity (center-left)
            const dateX = left; // date (left)

            // Head background.
            this.roundRect(ctx, left, tableTop, WIDTH - PAD * 2, 34, 10);
            ctx.fillStyle = COLORS.headBg;
            ctx.fill();

            ctx.fillStyle = COLORS.sub;
            ctx.font = `700 12px ${FONT_FAMILY}`;
            ctx.textAlign = 'right';
            ctx.fillText('دسته‌بندی', catX, tableTop + 18);
            ctx.textAlign = 'center';
            ctx.fillText('مقدار', qtyX, tableTop + 18);
            ctx.textAlign = 'left';
            ctx.fillText('تاریخ', dateX, tableTop + 18);

            y = tableTop + 34;

            if (items.length === 0) {
                ctx.textAlign = 'center';
                ctx.fillStyle = COLORS.muted;
                ctx.font = `400 13px ${FONT_FAMILY}`;
                ctx.fillText('موردی برای نمایش نیست.', WIDTH / 2, y + 19);
                y += 38;
            } else {
                items.forEach((item, index) => {
                    const rowY = y + index * 38;

                    if (index % 2 === 1) {
                        ctx.fillStyle = COLORS.panel;
                        ctx.fillRect(left, rowY, WIDTH - PAD * 2, 38);
                    }

                    ctx.fillStyle = COLORS.ink;
                    ctx.font = `700 13px ${FONT_FAMILY}`;
                    ctx.textAlign = 'right';
                    let categoryText = item.category || '-';
                    if (item.recordCount && item.recordCount > 1) {
                        categoryText += ` (${item.recordCount} رکورد)`;
                    }
                    ctx.fillText(this.truncate(ctx, categoryText, 300), catX, rowY + 19);

                    ctx.fillStyle = COLORS.ink;
                    ctx.font = `800 13px ${FONT_FAMILY}`;
                    ctx.textAlign = 'center';
                    ctx.fillText(`${item.quantity ?? '-'} ${item.unitLabel ?? ''}`.trim(), qtyX, rowY + 19);

                    ctx.fillStyle = COLORS.sub;
                    ctx.font = `400 12px ${FONT_FAMILY}`;
                    ctx.textAlign = 'left';
                    ctx.fillText(item.date || '-', dateX, rowY + 19);
                });

                y += items.length * 38;
            }

            // Table border.
            ctx.strokeStyle = COLORS.line;
            ctx.lineWidth = 1;
            this.roundRect(ctx, left, tableTop, WIDTH - PAD * 2, y - tableTop, 10);
            ctx.stroke();

            y += 20;

            // ---- Totals --------------------------------------------------
            this.roundRect(ctx, left, y, WIDTH - PAD * 2, 44, 12);
            ctx.fillStyle = COLORS.panel;
            ctx.fill();
            ctx.strokeStyle = COLORS.line;
            ctx.stroke();

            ctx.textAlign = 'right';
            ctx.fillStyle = COLORS.sub;
            ctx.font = `700 12px ${FONT_FAMILY}`;
            ctx.fillText('جمع مقدار', right - 14, y + 23);

            const totalsText = unitTotals.length
                ? unitTotals.map((t) => `${t.label}: ${t.total}`).join('   ·   ')
                : '-';
            ctx.textAlign = 'left';
            ctx.fillStyle = COLORS.ink;
            ctx.font = `800 13px ${FONT_FAMILY}`;
            ctx.fillText(this.truncate(ctx, totalsText, WIDTH - PAD * 2 - 120), left + 14, y + 23);

            y += 44 + 16;

            // ---- Footer --------------------------------------------------
            ctx.textAlign = 'center';
            ctx.fillStyle = COLORS.muted;
            ctx.font = `400 11px ${FONT_FAMILY}`;
            ctx.fillText('این رسید به‌صورت سیستمی صادر شده است.', WIDTH / 2, y + 8);

            return canvas;
        },

        infoRows() {
            const r = this.receipt;
            const rows = [
                { label: 'نوع گیرنده', value: r.recipientType || '-' },
                { label: 'نام گیرنده', value: r.recipientName || '-' },
                { label: 'کد ملی', value: r.nationalId || '-' },
            ];

            if (r.recipientCode !== null && r.recipientCode !== undefined) {
                rows.push({ label: r.recipientCodeLabel || 'کد', value: r.recipientCode || '-' });
            }
            if (r.mobile) {
                rows.push({ label: 'موبایل', value: r.mobile });
            }
            if (r.relation) {
                rows.push({ label: 'اطلاعات مرتبط', value: r.relation });
            }

            return rows;
        },

        truncate(ctx, text, maxWidth) {
            const str = (text ?? '').toString();
            if (ctx.measureText(str).width <= maxWidth) {
                return str;
            }
            let result = str;
            while (result.length > 1 && ctx.measureText(result + '…').width > maxWidth) {
                result = result.slice(0, -1);
            }
            return result + '…';
        },

        roundRect(ctx, x, y, w, h, radius) {
            const rr = Math.min(radius, w / 2, h / 2);
            ctx.beginPath();
            ctx.moveTo(x + rr, y);
            ctx.arcTo(x + w, y, x + w, y + h, rr);
            ctx.arcTo(x + w, y + h, x, y + h, rr);
            ctx.arcTo(x, y + h, x, y, rr);
            ctx.arcTo(x, y, x + w, y, rr);
            ctx.closePath();
        },

        dashedLine(ctx, x1, x2, y) {
            ctx.save();
            ctx.strokeStyle = COLORS.line;
            ctx.lineWidth = 1;
            ctx.setLineDash([4, 4]);
            ctx.beginPath();
            ctx.moveTo(x1, y);
            ctx.lineTo(x2, y);
            ctx.stroke();
            ctx.restore();
        },
    };
}
