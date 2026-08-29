export const MAX_MEDIA_FILE_BYTES = 15 * 1024 * 1024

export function isMediaFileWithinLimit(file: File, maxBytes = MAX_MEDIA_FILE_BYTES): boolean {
  return file.size <= maxBytes
}
