const TOKEN_KEY = "access_token";
const REFRESH_TOKEN_KEY = "refresh_token";
const USER_KEY = "auth_user";
const LAST_ACTIVITY_KEY = "auth_last_activity";

// Token
export const getToken = () => sessionStorage.getItem(TOKEN_KEY) || null;

export const setToken = (token) => {
  if (!token) return;
  sessionStorage.setItem(TOKEN_KEY, token);
};

export const removeToken = () => sessionStorage.removeItem(TOKEN_KEY);

export const getRefreshToken = () =>
  sessionStorage.getItem(REFRESH_TOKEN_KEY) || null;

export const setRefreshToken = (token) => {
  if (!token) return;
  sessionStorage.setItem(REFRESH_TOKEN_KEY, token);
};

export const removeRefreshToken = () =>
  sessionStorage.removeItem(REFRESH_TOKEN_KEY);

// User
export const getUser = () => {
  const user = sessionStorage.getItem(USER_KEY);

  if (!user) return null;

  try {
    return JSON.parse(user);
  } catch {
    sessionStorage.removeItem(USER_KEY);
    return null;
  }
};

export const setUser = (user) => {
  if (!user) return;
  sessionStorage.setItem(USER_KEY, JSON.stringify(user));
};

export const removeUser = () => sessionStorage.removeItem(USER_KEY);

export const getLastActivity = () => {
  const value = Number(sessionStorage.getItem(LAST_ACTIVITY_KEY));
  return Number.isFinite(value) && value > 0 ? value : null;
};

export const setLastActivity = (value = Date.now()) => {
  sessionStorage.setItem(LAST_ACTIVITY_KEY, String(value));
};

export const removeLastActivity = () =>
  sessionStorage.removeItem(LAST_ACTIVITY_KEY);

// Clear all auth
export const clearAuth = () => {
  removeToken();
  removeRefreshToken();
  removeUser();
  removeLastActivity();
};
