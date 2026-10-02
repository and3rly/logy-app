// Utilidades de texto

/** Minúsculas y sin tildes, para búsquedas que ignoran ambas */
export function normalizar(texto: string) {
  return texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
}
