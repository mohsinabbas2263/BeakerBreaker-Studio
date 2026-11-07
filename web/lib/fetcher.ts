import { api } from "./api";

export function fetcher<T>(path: string): Promise<T> {
  return api.get<T>(path);
}
