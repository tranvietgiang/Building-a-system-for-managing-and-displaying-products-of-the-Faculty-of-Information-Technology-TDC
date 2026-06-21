const DB_NAME = "product-upload-drafts";
const STORE_NAME = "draft-groups";
const DB_VERSION = 1;

const openDatabase = () =>
  new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onupgradeneeded = () => {
      const database = request.result;
      if (!database.objectStoreNames.contains(STORE_NAME)) {
        database.createObjectStore(STORE_NAME);
      }
    };

    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });

const runTransaction = async (mode, action) => {
  const database = await openDatabase();

  try {
    return await new Promise((resolve, reject) => {
      const transaction = database.transaction(STORE_NAME, mode);
      const store = transaction.objectStore(STORE_NAME);
      const request = action(store);

      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
      transaction.onerror = () => reject(transaction.error);
    });
  } finally {
    database.close();
  }
};

const migrateLegacyDrafts = async (legacyKey, targetKey) => {
  const rawDrafts = localStorage.getItem(legacyKey);
  if (!rawDrafts) return [];

  try {
    const legacyDrafts = JSON.parse(rawDrafts);
    const drafts = Array.isArray(legacyDrafts)
      ? legacyDrafts.map((draft) => ({
          ...draft,
          // File và blob URL cũ không thể khôi phục sau khi tải lại trang.
          images: (draft.images || []).filter(
            (image) => image.url && !image.url.startsWith("blob:"),
          ),
          files: [],
        }))
      : [];

    await saveDrafts(targetKey, drafts);
    localStorage.removeItem(legacyKey);
    return drafts;
  } catch {
    localStorage.removeItem(legacyKey);
    return [];
  }
};

export const getDrafts = async (draftKey, legacyKey) => {
  const drafts = await runTransaction("readonly", (store) =>
    store.get(draftKey),
  );

  if (Array.isArray(drafts)) return drafts;

  // Bản nháp tạo trước khi tách theo sinh viên được nhận bởi tài khoản
  // đang đăng nhập lần đầu, sau đó xóa khóa dùng chung để tránh lộ dữ liệu.
  if (legacyKey && legacyKey !== draftKey) {
    const legacyDrafts = await runTransaction("readonly", (store) =>
      store.get(legacyKey),
    );

    if (Array.isArray(legacyDrafts)) {
      await saveDrafts(draftKey, legacyDrafts);
      await runTransaction("readwrite", (store) => store.delete(legacyKey));
      return legacyDrafts;
    }

    return migrateLegacyDrafts(legacyKey, draftKey);
  }

  return migrateLegacyDrafts(draftKey, draftKey);
};

export const saveDrafts = (draftKey, drafts) =>
  runTransaction("readwrite", (store) => store.put(drafts, draftKey));

export const restoreDraftImages = (images = []) =>
  images
    .filter((image) => image.file || image.url)
    .map((image) => ({
      ...image,
      url: image.file ? URL.createObjectURL(image.file) : image.url,
    }));
