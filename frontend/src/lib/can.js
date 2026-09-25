// Single source of truth for frontend permission checks, backed by the
// `permissions` array Spatie computes server-side and AuthController
// returns on login (AuthController::login -> $user->getAllPermissions()).
//
// Prefer can()/canAny() over raw role checks wherever a matching Spatie
// permission exists. hasRole() is kept only as a thin wrapper for the
// remaining cases that have no equivalent permission yet.

function getStoredPermissions() {
  try {
    return JSON.parse(localStorage.getItem('csims_permissions') || '[]')
  } catch {
    return []
  }
}

function getStoredRoles() {
  try {
    return JSON.parse(localStorage.getItem('csims_roles') || '[]')
  } catch {
    return []
  }
}

export function can(permissionName) {
  return getStoredPermissions().includes(permissionName)
}

export function canAny(permissionNames = []) {
  const permissions = getStoredPermissions()
  return permissionNames.some((name) => permissions.includes(name))
}

// Thin wrapper on existing roles storage, for the rare case with no
// matching Spatie permission yet.
export function hasRole(roleName) {
  return getStoredRoles().includes(roleName)
}