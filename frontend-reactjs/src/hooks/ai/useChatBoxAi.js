import { useState } from "react";
import { aiApi } from "../../api";

export default function useChatBoxAi() {
  const [loadingAi, setLoadingAi] = useState(false);
  const [replyAi, setReplyAi] = useState("");

  const sendMessage = async (message) => {
    setLoadingAi(true);

    try {
      const res = await aiApi.sendMessage({ message });
      setReplyAi(res.reply);
      return res;
    } catch (err) {
      console.error("Error sending message to AI:", err);
      const fallback =
        err?.response?.data?.reply ||
        err?.response?.data?.message ||
        "Server error";
      setReplyAi(fallback);
      return { reply: fallback, products: [] };
    } finally {
      setLoadingAi(false);
    }
  };

  return {
    sendMessage,
    replyAi,
    loadingAi,
  };
}
