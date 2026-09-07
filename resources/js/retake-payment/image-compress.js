/**
 * Client-side downscale/re-encode before upload. A pasted screenshot can
 * easily be several MB at full screen resolution — uploading (and then
 * re-serving, e.g. on ACC's reconciliation page) that raw is what made the
 * page feel laggy. Resizes to a reasonable max dimension and re-encodes as
 * JPEG; falls back to the original file if decoding fails or compression
 * doesn't actually help (e.g. a small image, or one already a JPEG).
 */
export async function compressImage(file, { maxDimension = 1600, quality = 0.82 } = {}) {
    if (!file || !file.type?.startsWith('image/')) return file;

    let bitmap;
    try {
        bitmap = await createImageBitmap(file);
    } catch {
        return file;
    }

    const scale = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height));
    const width = Math.max(1, Math.round(bitmap.width * scale));
    const height = Math.max(1, Math.round(bitmap.height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(bitmap, 0, 0, width, height);
    bitmap.close?.();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
    if (!blob || blob.size >= file.size) return file;

    const name = (file.name || 'image').replace(/\.[^.]+$/, '') + '.jpg';
    return new File([blob], name, { type: 'image/jpeg' });
}
