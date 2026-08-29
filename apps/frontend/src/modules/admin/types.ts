export type AdminTableRow = { id: number; [key: string]: unknown }

export function toTableRows<T extends { id: number }>(items: T[]): AdminTableRow[] {
  return items as AdminTableRow[]
}
