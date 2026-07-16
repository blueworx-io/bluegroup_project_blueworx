/** Keep only string entries from an untrusted revalidation paths payload. */
export function filterPaths(input: unknown): string[] {
  return Array.isArray(input)
    ? input.filter((p): p is string => typeof p === "string")
    : [];
}
