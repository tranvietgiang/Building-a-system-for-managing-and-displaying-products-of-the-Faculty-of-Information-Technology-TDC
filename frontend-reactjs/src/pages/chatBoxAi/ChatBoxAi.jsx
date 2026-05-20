import React, { useEffect, useState, useRef } from "react";
import "./ChatBoxAi.css";
import useChatBoxAi from "../../hooks/ai/useChatBoxAi";
import { useNavigate } from "react-router-dom";
import { ROLE } from "../../utils/constants";
export default function ChatBoxAi({ user }) {
  const [isOpen, setIsOpen] = useState(false);
  const [isTyping, setIsTyping] = useState(false);
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState("");

  const navigate = useNavigate();
  const { sendMessage, loadingAi } = useChatBoxAi();
  const messagesEndRef = useRef(null);

  const userId = user?.user_id ?? "guest";
  const userName = user?.name;
  const userRole = user?.role ?? "guest";

  const STORAGE_KEY = userId ? `chat_${userId}` : null;
  const EXP_TIME = 10 * 60 * 1000;

  // =========================
  // LOAD MESSAGES KHI MỞ CHAT
  // =========================
  useEffect(() => {
    if (!isOpen) return;

    // Guest không lưu cache
    if (!STORAGE_KEY) {
      setMessages([
        {
          id: Date.now(),
          text: "Xin chào! Bạn cần đăng nhập để sử dụng trợ lý ảo 😊",
          sender: "bot",
        },
      ]);
      return;
    }

    const cached = localStorage.getItem(STORAGE_KEY);

    if (cached) {
      try {
        const parsed = JSON.parse(cached);
        if (Date.now() - parsed.time < EXP_TIME && parsed.messages?.length) {
          setMessages(parsed.messages);
          return;
        }
      } catch (e) {
        console.log(e);
        localStorage.removeItem(STORAGE_KEY);
      }
    }

    // Không có cache hợp lệ → greeting mới
    setMessages([
      {
        id: `bot_${Date.now()}`,
        text: `Xin chào ${userName || ""}! Mình có thể giúp gì cho bạn? 😊`,
        sender: "bot",
      },
    ]);
  }, [isOpen, STORAGE_KEY]);

  // =========================
  // RESET KHI ĐỔI USER (logout/login)
  // =========================
  useEffect(() => {
    setMessages([]);
    setInput("");
    setIsOpen(false);
  }, [userId]);

  // =========================
  // SCROLL
  // =========================
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages, isTyping]);

  // =========================
  // SAVE CACHE
  // =========================
  const saveToLocal = (msgs) => {
    if (!STORAGE_KEY) return;
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({ time: Date.now(), messages: msgs }),
    );
  };

  // =========================
  // SEND MESSAGE
  // =========================
  const handleSend = async () => {
    if (!input.trim() || loadingAi || isTyping) return;

    const userMessage = {
      id: `user_${Date.now()}_${Math.random()}`,
      text: input,
      sender: "user",
    };

    const newMessages = [...messages, userMessage];
    setMessages(newMessages);
    saveToLocal(newMessages);

    const messageToSend = input;
    setInput("");
    setIsTyping(true);

    try {
      const res = await sendMessage(messageToSend);

      const botReply = {
        id: `bot_${Date.now()}_${Math.random()}`,
        text: res?.reply ?? "AI không trả về dữ liệu",
        sender: "bot",
        products: res?.products || [],
      };

      const updated = [...newMessages, botReply];
      setMessages(updated);
      saveToLocal(updated);
    } catch (err) {
      console.error(err);

      const errorMsg = {
        id: `bot_${Date.now()}_${Math.random()}`,
        text: "Lỗi kết nối server, vui lòng thử lại 😥",
        sender: "bot",
      };

      const updated = [...newMessages, errorMsg];
      setMessages(updated);
      saveToLocal(updated);
    } finally {
      setIsTyping(false);
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === "Enter") handleSend();
  };

  const handleViewDetail = (id) => {
    if (!userRole) return;

    let url = "/visitor-detail";

    if (userRole == ROLE.TEACHER) {
      url = "/detail-teacher";
    } else if (userRole == ROLE.STUDENT) {
      url = "/detail";
    } else if (userRole == "guest") {
      url = "/visitor-detail";
    }

    navigate(url, {
      state: { productId: id },
    });

    setIsOpen(false);
  };

  // Guest không hiển thị chatbox
  if (!userId) return null;

  return (
    <>
      {/* Nút chat nổi */}
      {!isOpen && (
        <button
          onClick={() => setIsOpen(true)}
          className="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full p-4 shadow-lg hover:shadow-xl hover:scale-110 transition-all duration-300"
          aria-label="Mở chat"
        >
          <div className="relative">
            <svg
              className="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
              />
            </svg>
            <span className="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full animate-pulse"></span>
          </div>
        </button>
      )}

      {/* Chat window */}
      {isOpen && (
        <div className="fixed bottom-6 right-6 z-50 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-200 animate-slide-up">
          {/* Header */}
          <div className="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
              <h3 className="text-white font-semibold">Trợ lý ảo</h3>
            </div>
            <button
              onClick={() => setIsOpen(false)}
              className="text-white/80 hover:text-white transition"
            >
              <svg
                className="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          {/* Messages */}
          <div className="h-96 overflow-y-auto p-4 space-y-3 bg-gray-50">
            {messages.map((msg) => (
              <div key={msg.id}>
                <div
                  className={`flex items-end gap-2 ${msg.sender === "user" ? "justify-end" : "justify-start"}`}
                >
                  {/* Avatar bot */}
                  {msg.sender === "bot" && (
                    <div className="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                      AI
                    </div>
                  )}

                  <div className="flex flex-col gap-1 max-w-[80%]">
                    <span
                      className={`text-xs text-gray-400 ${msg.sender === "user" ? "text-right" : "text-left"}`}
                    >
                      {msg.sender === "user" ? userName || "Bạn" : "Trợ lý ảo"}
                    </span>
                    <div
                      className={`px-4 py-2 rounded-2xl ${
                        msg.sender === "user"
                          ? "bg-blue-600 text-white"
                          : "bg-white text-gray-800 shadow-sm border"
                      }`}
                    >
                      <p className="text-sm whitespace-pre-line">{msg.text}</p>
                    </div>
                  </div>

                  {/* Avatar user */}
                  {msg.sender === "user" && (
                    <div className="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                      {userName?.charAt(0)?.toUpperCase() || "U"}
                    </div>
                  )}
                </div>

                {/* Products */}
                {msg.products?.length > 0 && (
                  <div className="mt-2 ml-9 pt-2 border-t border-gray-200">
                    <p className="text-xs text-gray-400 mb-1">
                      Đồ án liên quan:
                    </p>
                    <div className="flex flex-wrap gap-1">
                      {msg.products.map((p) => (
                        <button
                          key={p.id}
                          onClick={() => handleViewDetail(p.id)}
                          className="text-xs bg-blue-50 text-blue-600 border border-blue-200 rounded-full px-2 py-0.5 hover:bg-blue-100 transition"
                        >
                          {p.title}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            ))}

            {/* Typing indicator */}
            {isTyping && (
              <div className="flex items-end gap-2 justify-start">
                <div className="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                  AI
                </div>
                <div className="bg-white text-gray-800 shadow-sm border rounded-2xl px-4 py-3">
                  <div className="flex items-center gap-1">
                    <span
                      className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                      style={{ animationDelay: "0ms" }}
                    ></span>
                    <span
                      className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                      style={{ animationDelay: "150ms" }}
                    ></span>
                    <span
                      className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                      style={{ animationDelay: "300ms" }}
                    ></span>
                  </div>
                </div>
              </div>
            )}

            <div ref={messagesEndRef} />
          </div>

          {/* Input */}
          <div className="p-3 border-t border-gray-200 bg-white">
            <div className="flex gap-2">
              <input
                type="text"
                value={input}
                onChange={(e) => {
                  if (e.target.value.length <= 1000) {
                    setInput(e.target.value);
                  }
                }}
                onKeyPress={handleKeyPress}
                placeholder="Nhập tin nhắn..."
                disabled={isTyping}
                maxLength={1000}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
              />
              <button
                onClick={handleSend}
                disabled={!input.trim() || isTyping}
                className="p-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg
                  className="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                  />
                </svg>
              </button>
            </div>
            <p className="text-xs text-center text-gray-400 mt-2">
              Trợ lý ảo • Hỗ trợ 24/7
            </p>
          </div>
        </div>
      )}
    </>
  );
}
