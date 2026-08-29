import apiClient from './client'
import { isMediaFileWithinLimit } from '@/lib/mediaUploadLimits'
import type { MediaUploadResult } from '@/types/article'

export class MediaFileTooLargeError extends Error {
  constructor() {
    super('validation.media_file_too_large')
    this.name = 'MediaFileTooLargeError'
  }
}

export async function uploadAdminMedia(file: File): Promise<MediaUploadResult> {
  if (!isMediaFileWithinLimit(file)) {
    throw new MediaFileTooLargeError()
  }

  const body = new FormData()
  body.append('file', file)

  const { data } = await apiClient.post<MediaUploadResult>('/admin/media/upload', body, {
    headers: {
      'Content-Type': undefined,
    },
  })

  return data
}
