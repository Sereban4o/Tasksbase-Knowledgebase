/**
 * Helpers for normalising the different API response shapes used across the app.
 * The backend returns plain arrays, Laravel paginators ({ data: [...] }) and
 * legacy wrappers ({ value: [...] }) depending on the endpoint.
 */

type Json = Record<string, any> | any[] | null | undefined

/** Extract a list from any of the response shapes. Always returns an array. */
export function unwrapList<T = any>(res: Json): T[] {
  const data: any = (res as any)?.data ?? res

  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data?.value)) return data.value
  if (Array.isArray(data?.data?.data)) return data.data.data

  return []
}

/** Extract a single object from a response, unwrapping the `data` envelope. */
export function unwrapData<T = any>(res: Json): T | null {
  const data: any = (res as any)?.data ?? res

  if (data && typeof data === 'object' && !Array.isArray(data)) {
    if (data.data && typeof data.data === 'object' && !Array.isArray(data.data)) {
      return data.data as T
    }
    return data as T
  }

  return null
}
