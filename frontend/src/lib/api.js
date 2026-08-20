const API_BASE_URL = 'http://127.0.0.1:8000/api/v1'

export async function login(email, password) {
  const response = await fetch(`${API_BASE_URL}/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  })

  const data = await response.json()

  if (!response.ok) {
    throw new Error(data.message || 'Login failed')
  }

  return data
}

function authHeaders() {
  const token = localStorage.getItem('csims_token')
  return {
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`,
  }
}

export async function apiGet(path) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: authHeaders(),
  })

  const data = await response.json()

  if (!response.ok) {
    throw new Error(data.message || 'Request failed')
  }

  return data
}

export async function getUnreadNotifications() {
  return apiGet('/notifications/unread')
}

export async function markNotificationRead(id) {
  return apiPatch(`/notifications/${id}/read`, {})
}

export async function apiPost(path, body) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'POST',
    headers: {
      ...authHeaders(),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  })

  const data = await response.json()

  if (!response.ok) {
    throw new Error(data.message || 'Request failed')
  }

  return data
}

export async function apiPut(path, body) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'PUT',
    headers: {
      ...authHeaders(),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  })

  const data = await response.json()

  if (!response.ok) {
    throw new Error(data.message || 'Request failed')
  }

  return data
}

export async function apiPatch(path, body) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'PATCH',
    headers: {
      ...authHeaders(),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  })

  const data = await response.json()

  if (!response.ok) {
    throw new Error(data.message || 'Request failed')
  }

  return data
}

export async function apiDelete(path) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'DELETE',
    headers: authHeaders(),
  })

  const data = await response.json()

  if (!response.ok) {
    throw new Error(data.message || 'Request failed')
  }

  return data
}