// Tipos para columnas de tabla
export interface DynamicColumn {
  key: string
  label: string
  type: 'text' | 'number' | 'date' | 'boolean' | 'email' | 'phone' | 'url' | 'image' | 'select' | 'search' | 'custom'
  sortable?: boolean
  filterable?: boolean
  width?: string
  align?: 'left' | 'center' | 'right'
  formatter?: (value: any, item: any) => string
  renderer?: (value: any, item: any) => any
  options?: Array<{ value: any; label: string }>
  searchEndpoint?: string
  filterField?: string
  hideInTable?: boolean
  hideInModal?: boolean
}

// Tipos para campos de modal
export interface DynamicField {
  key: string
  label: string
  type: 'text' | 'number' | 'date' | 'boolean' | 'email' | 'phone' | 'url' | 'file' | 'textarea' | 'select' | 'search' | 'qr'
  required?: boolean
  placeholder?: string
  validation?: string
  defaultValue?: any
  options?: Array<{ value: any; label: string }>
  searchEndpoint?: string
  description?: string
  checkboxLabel?: string
  hideInForm?: boolean
  hideInView?: boolean
}

// Configuración de tabla
export interface DynamicTableConfig {
  columns: DynamicColumn[]
  options?: Record<string, Array<{ value: any; label: string }>>
  searchColumns?: string[]
  relations?: string[]
  actions?: DynamicAction[]
  bulkActions?: DynamicBulkAction[]
  rowKey?: string
  selectable?: boolean
  sortable?: boolean
  filterable?: boolean
  searchable?: boolean
}

// Configuración de modal
export interface DynamicModalConfig {
  fields: DynamicField[]
  title?: string
  submitText?: string
  data?: any
  mode?: 'create' | 'edit' | 'view'
}

// Acciones de tabla
export interface DynamicAction {
  key: string
  label: string
  icon?: string
  color?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info'
  condition?: (item: any) => boolean
  handler?: (item: any) => void
}

// Acciones masivas
export interface DynamicBulkAction {
  key: string
  label: string
  icon?: string
  color?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info'
  handler?: (items: any[]) => void
}

// Paginación
export interface DynamicPagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
  has_more_pages: boolean
  next_page_url?: string
  prev_page_url?: string
}

// Filtros
export interface DynamicFilter {
  key: string
  value: any
  operator?: 'eq' | 'ne' | 'gt' | 'gte' | 'lt' | 'lte' | 'like' | 'in' | 'between'
}

// Respuesta de API
export interface DynamicApiResponse<T = any> {
  success: boolean
  data: T
  pagination?: DynamicPagination
  filters?: Record<string, any>
  stats?: Record<string, any>
  message?: string
  errors?: Record<string, string[]>
}

// Configuración de metadatos
export interface DynamicMetadata {
  table: DynamicTableConfig
  modal: DynamicModalConfig
  model: {
    name: string
    table: string
    fillable: string[]
    casts: Record<string, string>
  }
}

// Configuración de la librería
export interface DynamicUIConfig {
  cacheTimeout: number
  metadataEndpoint: string
  dataEndpoint: string
  axios?: any
  pagination: {
    perPage: number
    perPageOptions: number[]
  }
  filters: {
    enabled: boolean
    prefix: string
  }
  search: {
    enabled: boolean
    placeholder: string
  }
}

// Estado de carga
export interface DynamicLoadingState {
  loading: boolean
  error: string | null
  data: any[] | null
  pagination: DynamicPagination | null
  filters: Record<string, any>
  search: string
  sortBy: string
  order: 'asc' | 'desc'
  perPage: number
  selectedItems: any[]
}

// Eventos de tabla
export interface DynamicTableEvents {
  'data-loaded': (data: DynamicApiResponse) => void
  'action': (action: { action: string; item: any }) => void
  'selection-change': (items: any[]) => void
  'filter-change': (filters: Record<string, any>) => void
  'search-change': (search: string) => void
  'sort-change': (sortBy: string, order: 'asc' | 'desc') => void
  'page-change': (page: number) => void
  'per-page-change': (perPage: number) => void
}

// Eventos de modal
export interface DynamicModalEvents {
  'close': () => void
  'success': (data: any) => void
  'error': (error: any) => void
  'submit': (data: any) => void
  'cancel': () => void
}