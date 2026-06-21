import { useState, useEffect } from "react";
import { AuthContext } from "./AuthContext";
import {
  getToken,
  getUser,
  setToken,
  setRefreshToken,
  getRefreshToken,
  setUser as setStoredUser,
  clearAuth,
  removeUser,
} from "../utils/storage";
import authApi from "../api/auth.api";

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

  const logout = async () => {
    try {
      await authApi.logout({ refresh_token: getRefreshToken() });
    } catch (err) {
      console.log(err);
    } finally {
      clearAuth();
      setUserState(null);
      setTokenState(null);
    }
  };

  return (
    <AuthContext.Provider
      value={{ user, setUser, token, isAuthenticated, login, logout, loading }}
    >
      {children}
    </AuthContext.Provider>
  );
};
