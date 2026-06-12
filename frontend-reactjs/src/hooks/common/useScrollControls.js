import { useCallback, useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";

export default function useScrollControls() {
  const navigate = useNavigate();
  const location = useLocation();

  const scrollToTop = useCallback(() => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  }, []);

  const scrollToTarget = useCallback((targetId = null) => {
    const target = targetId ? document.getElementById(targetId) : null;

    if (target) {
      target.scrollIntoView({ behavior: "smooth", block: "start" });
      return;
    }

    const bottom = Math.max(
      document.body.scrollHeight,
      document.documentElement.scrollHeight,
    );

    window.scrollTo({
      top: bottom,
      behavior: "smooth",
    });
  }, []);

  const handleTop = useCallback(
    (path = "/nckh-visitor") => {
      if (location.pathname === path) {
        scrollToTop();
        return;
      }

      navigate(path, { state: { scrollTo: "top" } });
    },
    [location.pathname, navigate, scrollToTop],
  );

  const handleBottom = useCallback(
    (path = "/nckh-visitor", targetId = "san-pham") => {
      if (location.pathname === path) {
        scrollToTarget(targetId);
        return;
      }

      navigate(`${path}#${targetId}`, { state: { scrollTo: targetId } });
    },
    [location.pathname, navigate, scrollToTarget],
  );

  useEffect(() => {
    const scrollTo = location.state?.scrollTo || location.hash.replace("#", "");
    if (!scrollTo) return;

    const timer = window.setTimeout(() => {
      if (scrollTo === "top") {
        scrollToTop();
        return;
      }

      scrollToTarget(scrollTo);
    }, 120);

    return () => window.clearTimeout(timer);
  }, [location.hash, location.state, scrollToTarget, scrollToTop]);

  return {
    handleTop,
    handleBottom,
    handlePageTop: scrollToTop,
    handlePageBottom: scrollToTarget,
  };
}
