# Ejemplos de Integración con el Frontend

Este documento proporciona ejemplos de cómo integrar los endpoints de la API en tu aplicación frontend (React, Vue, etc.).

## Configuración Inicial

### Axios Instance

Crea una instancia de Axios configurada con el token JWT:

```typescript
// utils/api.ts
import axios from 'axios';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'https://cms.productoscasaalba.cl/wp-json';

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor para agregar el token JWT
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor para manejar errores de autenticación
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expirado o inválido, redirigir al login
      localStorage.removeItem('jwt_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

## Servicios

### Customer Service

```typescript
// services/customer.service.ts
import { api } from '@/utils/api';

export interface CustomerProfile {
  id: number;
  email: string;
  username: string;
  first_name: string;
  last_name: string;
  display_name: string;
  phone: string;
  date_created: string;
  orders_count: number;
  total_spent: number;
}

export interface CustomerAddress {
  first_name: string;
  last_name: string;
  company: string;
  address_1: string;
  address_2: string;
  city: string;
  state: string;
  postcode: string;
  country: string;
  phone?: string;
  email?: string;
}

export interface CustomerAddresses {
  billing: CustomerAddress;
  shipping: CustomerAddress;
}

export interface UpdateProfileData {
  first_name?: string;
  last_name?: string;
  display_name?: string;
  phone?: string;
  email?: string;
  current_password?: string;
  new_password?: string;
}

class CustomerService {
  private baseUrl = '/casa-alba/v1/customer';

  async getProfile(): Promise<CustomerProfile> {
    const response = await api.get<CustomerProfile>(`${this.baseUrl}/profile`);
    return response.data;
  }

  async updateProfile(data: UpdateProfileData): Promise<CustomerProfile> {
    const response = await api.put<{ message: string; profile: CustomerProfile }>(
      `${this.baseUrl}/profile`,
      data
    );
    return response.data.profile;
  }

  async getAddresses(): Promise<CustomerAddresses> {
    const response = await api.get<CustomerAddresses>(`${this.baseUrl}/addresses`);
    return response.data;
  }

  async updateAddresses(data: Partial<CustomerAddresses>): Promise<CustomerAddresses> {
    const response = await api.put<{ message: string; addresses: CustomerAddresses }>(
      `${this.baseUrl}/addresses`,
      data
    );
    return response.data.addresses;
  }
}

export const customerService = new CustomerService();
```

### Orders Service

```typescript
// services/orders.service.ts
import { api } from '@/utils/api';

export interface OrderItem {
  id: number;
  product_id: number;
  variation_id: number;
  name: string;
  quantity: number;
  subtotal: number;
  subtotal_formatted: string;
  total: number;
  total_formatted: string;
  sku: string;
  image: string;
}

export interface OrderListItem {
  id: number;
  order_number: string;
  date_created: string;
  date_created_gmt: string;
  status: string;
  status_label: string;
  total: number;
  total_formatted: string;
  currency: string;
  payment_method: string;
  payment_method_title: string;
  items_count: number;
}

export interface OrderDetail extends OrderListItem {
  order_key: string;
  date_modified: string | null;
  subtotal: number;
  subtotal_formatted: string;
  total_tax: number;
  total_tax_formatted: string;
  shipping_total: number;
  shipping_total_formatted: string;
  discount_total: number;
  discount_total_formatted: string;
  customer_note: string;
  billing: CustomerAddress;
  shipping: CustomerAddress;
  line_items: OrderItem[];
  shipping_lines: Array<{
    id: number;
    method_title: string;
    method_id: string;
    total: number;
    total_formatted: string;
  }>;
  links: {
    pay?: { url: string; label: string };
    cancel?: { url: string; label: string };
    reorder?: { url: string; label: string };
  };
}

export interface OrdersListResponse {
  orders: OrderListItem[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    total_pages: number;
  };
}

class OrdersService {
  private baseUrl = '/casa-alba/v1/customer/orders';

  async getOrders(page = 1, perPage = 10, status = 'any'): Promise<OrdersListResponse> {
    const response = await api.get<OrdersListResponse>(this.baseUrl, {
      params: { page, per_page: perPage, status },
    });
    return response.data;
  }

  async getOrder(orderId: number): Promise<OrderDetail> {
    const response = await api.get<OrderDetail>(`${this.baseUrl}/${orderId}`);
    return response.data;
  }

  async cancelOrder(orderId: number): Promise<{ message: string; order_id: number; status: string }> {
    const response = await api.post(`${this.baseUrl}/${orderId}/cancel`);
    return response.data;
  }

  async reorder(orderId: number): Promise<{
    message: string;
    items_added: number;
    cart_item_count: number;
    items_failed?: Array<{ name: string; reason: string }>;
    warning?: string;
  }> {
    const response = await api.post(`${this.baseUrl}/${orderId}/reorder`);
    return response.data;
  }
}

export const ordersService = new OrdersService();
```

## Hooks de React

### useProfile Hook

```typescript
// hooks/useProfile.ts
import { useState, useEffect } from 'react';
import { customerService, CustomerProfile } from '@/services/customer.service';

export function useProfile() {
  const [profile, setProfile] = useState<CustomerProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadProfile();
  }, []);

  const loadProfile = async () => {
    try {
      setLoading(true);
      const data = await customerService.getProfile();
      setProfile(data);
      setError(null);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error al cargar el perfil');
    } finally {
      setLoading(false);
    }
  };

  const updateProfile = async (data: any) => {
    try {
      setLoading(true);
      const updated = await customerService.updateProfile(data);
      setProfile(updated);
      setError(null);
      return { success: true };
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al actualizar el perfil';
      setError(errorMessage);
      return { success: false, error: errorMessage };
    } finally {
      setLoading(false);
    }
  };

  return {
    profile,
    loading,
    error,
    updateProfile,
    reload: loadProfile,
  };
}
```

### useOrders Hook

```typescript
// hooks/useOrders.ts
import { useState, useEffect } from 'react';
import { ordersService, OrderListItem, OrderDetail } from '@/services/orders.service';

export function useOrders(page = 1, perPage = 10, status = 'any') {
  const [orders, setOrders] = useState<OrderListItem[]>([]);
  const [pagination, setPagination] = useState({
    page: 1,
    per_page: 10,
    total: 0,
    total_pages: 0,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadOrders();
  }, [page, perPage, status]);

  const loadOrders = async () => {
    try {
      setLoading(true);
      const data = await ordersService.getOrders(page, perPage, status);
      setOrders(data.orders);
      setPagination(data.pagination);
      setError(null);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error al cargar los pedidos');
    } finally {
      setLoading(false);
    }
  };

  return {
    orders,
    pagination,
    loading,
    error,
    reload: loadOrders,
  };
}

export function useOrderDetail(orderId: number) {
  const [order, setOrder] = useState<OrderDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadOrder();
  }, [orderId]);

  const loadOrder = async () => {
    try {
      setLoading(true);
      const data = await ordersService.getOrder(orderId);
      setOrder(data);
      setError(null);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error al cargar el pedido');
    } finally {
      setLoading(false);
    }
  };

  const cancelOrder = async () => {
    try {
      await ordersService.cancelOrder(orderId);
      await loadOrder(); // Recargar el pedido
      return { success: true };
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al cancelar el pedido';
      return { success: false, error: errorMessage };
    }
  };

  const reorder = async () => {
    try {
      const result = await ordersService.reorder(orderId);
      return { success: true, data: result };
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al volver a pedir';
      return { success: false, error: errorMessage };
    }
  };

  return {
    order,
    loading,
    error,
    cancelOrder,
    reorder,
    reload: loadOrder,
  };
}
```

## Componentes de Ejemplo

### Perfil de Usuario

```tsx
// components/UserProfile.tsx
import { useProfile } from '@/hooks/useProfile';

export function UserProfile() {
  const { profile, loading, error, updateProfile } = useProfile();

  if (loading) return <div>Cargando...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!profile) return null;

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    
    const result = await updateProfile({
      first_name: formData.get('first_name'),
      last_name: formData.get('last_name'),
      phone: formData.get('phone'),
    });

    if (result.success) {
      alert('Perfil actualizado correctamente');
    } else {
      alert(`Error: ${result.error}`);
    }
  };

  return (
    <div>
      <h2>Mi Perfil</h2>
      <form onSubmit={handleSubmit}>
        <input
          name="first_name"
          defaultValue={profile.first_name}
          placeholder="Nombre"
        />
        <input
          name="last_name"
          defaultValue={profile.last_name}
          placeholder="Apellido"
        />
        <input
          name="phone"
          defaultValue={profile.phone}
          placeholder="Teléfono"
        />
        <button type="submit">Guardar</button>
      </form>
    </div>
  );
}
```

### Lista de Pedidos

```tsx
// components/OrdersList.tsx
import { useOrders } from '@/hooks/useOrders';
import Link from 'next/link';

export function OrdersList() {
  const { orders, pagination, loading, error } = useOrders(1, 10);

  if (loading) return <div>Cargando pedidos...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      <h2>Mis Pedidos</h2>
      <div>
        {orders.map((order) => (
          <div key={order.id} className="order-item">
            <Link href={`/cuenta/pedidos/${order.id}`}>
              <h3>Pedido #{order.order_number}</h3>
            </Link>
            <p>Fecha: {new Date(order.date_created).toLocaleDateString()}</p>
            <p>Estado: {order.status_label}</p>
            <p>Total: {order.total_formatted}</p>
            <p>Items: {order.items_count}</p>
          </div>
        ))}
      </div>
      <div>
        <p>
          Página {pagination.page} de {pagination.total_pages}
        </p>
        <p>Total de pedidos: {pagination.total}</p>
      </div>
    </div>
  );
}
```

### Detalle de Pedido

```tsx
// components/OrderDetail.tsx
import { useOrderDetail } from '@/hooks/useOrders';
import { useRouter } from 'next/router';

export function OrderDetail({ orderId }: { orderId: number }) {
  const { order, loading, error, cancelOrder, reorder } = useOrderDetail(orderId);
  const router = useRouter();

  if (loading) return <div>Cargando pedido...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!order) return null;

  const handleCancel = async () => {
    if (confirm('¿Estás seguro de que deseas cancelar este pedido?')) {
      const result = await cancelOrder();
      if (result.success) {
        alert('Pedido cancelado correctamente');
      } else {
        alert(`Error: ${result.error}`);
      }
    }
  };

  const handleReorder = async () => {
    const result = await reorder();
    if (result.success) {
      alert(`${result.data.items_added} productos agregados al carrito`);
      router.push('/carrito');
    } else {
      alert(`Error: ${result.error}`);
    }
  };

  return (
    <div>
      <h2>Pedido #{order.order_number}</h2>
      
      <div>
        <p>Estado: {order.status_label}</p>
        <p>Fecha: {new Date(order.date_created).toLocaleDateString()}</p>
        <p>Total: {order.total_formatted}</p>
      </div>

      <h3>Productos</h3>
      <div>
        {order.line_items.map((item) => (
          <div key={item.id}>
            <img src={item.image} alt={item.name} width={50} />
            <h4>{item.name}</h4>
            <p>Cantidad: {item.quantity}</p>
            <p>Total: {item.total_formatted}</p>
          </div>
        ))}
      </div>

      <h3>Dirección de Envío</h3>
      <div>
        <p>{order.shipping.first_name} {order.shipping.last_name}</p>
        <p>{order.shipping.address_1}</p>
        {order.shipping.address_2 && <p>{order.shipping.address_2}</p>}
        <p>{order.shipping.city}, {order.shipping.state} {order.shipping.postcode}</p>
      </div>

      <div className="actions">
        {order.links.pay && (
          <a href={order.links.pay.url} className="btn btn-primary">
            {order.links.pay.label}
          </a>
        )}
        {order.links.cancel && (
          <button onClick={handleCancel} className="btn btn-danger">
            {order.links.cancel.label}
          </button>
        )}
        {order.links.reorder && (
          <button onClick={handleReorder} className="btn btn-secondary">
            {order.links.reorder.label}
          </button>
        )}
      </div>
    </div>
  );
}
```

## Manejo de Errores

```typescript
// utils/errorHandler.ts
export function handleApiError(error: any): string {
  if (error.response) {
    // Error de respuesta del servidor
    const { data, status } = error.response;
    
    switch (status) {
      case 400:
        return data?.message || 'Datos inválidos';
      case 401:
        return 'Sesión expirada. Por favor inicia sesión nuevamente';
      case 404:
        return data?.message || 'Recurso no encontrado';
      case 409:
        return data?.message || 'Conflicto con los datos existentes';
      case 500:
        return 'Error del servidor. Por favor intenta más tarde';
      default:
        return data?.message || 'Error desconocido';
    }
  } else if (error.request) {
    // Error de red
    return 'Error de conexión. Verifica tu internet';
  } else {
    // Error de configuración
    return error.message || 'Error desconocido';
  }
}
```

## Testing

### Ejemplo con Jest

```typescript
// services/__tests__/customer.service.test.ts
import { customerService } from '../customer.service';
import { api } from '@/utils/api';

jest.mock('@/utils/api');

describe('CustomerService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should get customer profile', async () => {
    const mockProfile = {
      id: 1,
      email: 'test@example.com',
      first_name: 'Test',
      last_name: 'User',
    };

    (api.get as jest.Mock).mockResolvedValue({ data: mockProfile });

    const result = await customerService.getProfile();

    expect(api.get).toHaveBeenCalledWith('/casa-alba/v1/customer/profile');
    expect(result).toEqual(mockProfile);
  });

  it('should update customer profile', async () => {
    const updateData = { first_name: 'John' };
    const mockResponse = {
      message: 'Profile updated',
      profile: { ...updateData, id: 1 },
    };

    (api.put as jest.Mock).mockResolvedValue({ data: mockResponse });

    const result = await customerService.updateProfile(updateData);

    expect(api.put).toHaveBeenCalledWith(
      '/casa-alba/v1/customer/profile',
      updateData
    );
    expect(result).toEqual(mockResponse.profile);
  });
});
```

