import { useState, useEffect, useCallback } from "react";
import { AuthContext } from "./AuthContext";
import {
  getToken,
  getUser,
  setToken,
  setRefreshToken,
  getRefreshToken,
  getLastActivity,
  setLastActivity,
  setUser as setStoredUser,
  clearAuth,
  removeUser,
} from "../utils/storage";
import authApi from "../api/auth.api";

const IDLE_TIMEOUT_MINUTES = Number(
  import.meta.env.VITE_AUTH_IDLE_TIMEOUT_MINUTES ?? 60,
);
const IDLE_TIMEOUT_MS = Math.max(1, IDLE_TIMEOUT_MINUTES) * 60 * 1000;
const ACTIVITY_EVENTS = [
  "click",
  "keydown",
  "mousemove",
  "scroll",
  "touchstart",
  "focus",
];

export const AuthProvider = ({ children }) => {
  const [user, setUserState] = useState(getUser());
  const [token, setTokenState] = useState(getToken());
  const [loading, setLoading] = useState(true);

  const isAuthenticated = !!token;

  useEffect(() => {
    const fetchUser = async () => {
      if (!token) {
        setLoading(false);
        return;
      }

      if (user) {
        setLoading(false);
        return;
      }

      try {
        const res = await authApi.me();
        const currentUser = res.user ?? res;

        setStoredUser(currentUser);
        setUserState(currentUser);
      } catch (err) {
        clearAuth();
        setUserState(null);
        setTokenState(null);
        console.log(err);
      } finally {
        setLoading(false);
      }
    };

    fetchUser();
  }, [token, user]);

  const login = async (data) => {
    try {
      const res = await authApi.login(data);

      if (!res?.success || !res?.token || !res?.refresh_token || !res?.user) {
        throw new Error(res?.message || "Sai tài khoản hoặc mật khẩu!");
      }

      setToken(res.token);
      setRefreshToken(res.refresh_token);
      setLastActivity();
      setTokenState(res.token);

      setStoredUser(res.user);
      setUserState(res.user);

      return res;
    } catch (error) {
      clearAuth();
      setTokenState(null);
      setUserState(null);
      throw error;
    }
  };

  const setUser = (nextUser) => {
    if (!nextUser) {
      removeUser();
      setUserState(null);
      return;
    }

    setStoredUser(nextUser);
    setUserState(nextUser);
  };

  const logout = useCallback(async () => {
    try {
      await authApi.logout({ refresh_token: getRefreshToken() });
    } catch (err) {
      console.log(err);
    } finally {
      clearAuth();
      setUserState(null);
      setTokenState(null);
    }
  }, []);

  useEffect(() => {
    if (!token) return undefined;

    const isIdleExpired = () => {
      const lastActivity = getLastActivity();

      if (!lastActivity) {
        setLastActivity();
        return false;
      }

      return Date.now() - lastActivity >= IDLE_TIMEOUT_MS;
    };
    const checkIdleSession = () => {
      if (isIdleExpired()) {
        logout();
      }
    };
    const refreshActivity = () => {
      if (isIdleExpired()) {
        logout();
        return;
      }

      setLastActivity();
    };

    if (!getLastActivity()) {
      setLastActivity();
    }

    ACTIVITY_EVENTS.forEach((eventName) => {
      window.addEventListener(eventName, refreshActivity, { passive: true });
    });

    const intervalId = window.setInterval(checkIdleSession, 30 * 1000);
    checkIdleSession();

    return () => {
      ACTIVITY_EVENTS.forEach((eventName) => {
        window.removeEventListener(eventName, refreshActivity);
      });
      window.clearInterval(intervalId);
    };
  }, [logout, token]);

  return (
    <AuthContext.Provider
      value={{ user, setUser, token, isAuthenticated, login, logout, loading }}
    >
      {children}
    </AuthContext.Provider>
  );
};
