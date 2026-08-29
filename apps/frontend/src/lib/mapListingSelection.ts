export interface DistrictRef {
  id: number
  slug: string
}

export function resolveDistrictSlug(districts: DistrictRef[], districtId: number): string | null {
  const district = districts.find((item) => item.id === districtId)
  return district?.slug ?? null
}

export function shouldToggleCloseOnReselect(currentId: number | null, nextId: number): boolean {
  return currentId === nextId
}

export function sliceListingImages(images: string[], maxSlides = 4): string[] {
  return images.slice(0, maxSlides)
}
