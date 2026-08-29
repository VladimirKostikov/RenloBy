const ACCEPTED_PROFILE_IMAGE_TYPES = new Set([
  'image/jpeg',
  'image/png',
  'image/webp',
  'image/gif',
])

export function isAcceptedProfileImage(file: File): boolean {
  if (ACCEPTED_PROFILE_IMAGE_TYPES.has(file.type)) {
    return true
  }

  const name = file.name.toLowerCase()
  return (
    name.endsWith('.jpg')
    || name.endsWith('.jpeg')
    || name.endsWith('.png')
    || name.endsWith('.webp')
    || name.endsWith('.gif')
  )
}

export function pickFirstAcceptedImage(files: FileList | File[] | null | undefined): File | null {
  if (!files) {
    return null
  }

  for (const file of Array.from(files)) {
    if (isAcceptedProfileImage(file)) {
      return file
    }
  }

  return null
}
