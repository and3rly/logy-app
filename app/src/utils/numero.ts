// Formato de números en pantalla: montos con los decimales de los parámetros de la empresa
// (llegan con el usuario de la sesión) y cantidades sin decimales de sobra
import { useSesionStore } from '../stores/sesion'

// Decimales de montos configurados en Parámetros; sin definir, 2
export function decimalesMonto(): number {
  const valor = useSesionStore().usuario?.decimal_monto
  return valor === null || valor === undefined ? 2 : Number(valor)
}

// 1234.5 → "1,234.50" (con 2 decimales configurados)
export function formatoMonto(valor: number | string | null | undefined): string {
  const decimales = decimalesMonto()

  return Number(valor ?? 0).toLocaleString('en-US', {
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales
  })
}

// Cantidades y existencias: 12 → "12", 0.5 → "0.5", 1.257 → "1.26"
export function formatoCantidad(valor: number | string | null | undefined): string {
  return Number(valor ?? 0).toLocaleString('en-US', {
    maximumFractionDigits: 2
  })
}
