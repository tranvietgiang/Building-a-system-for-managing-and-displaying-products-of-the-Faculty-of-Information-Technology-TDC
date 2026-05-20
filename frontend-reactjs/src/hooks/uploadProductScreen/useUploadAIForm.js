import useUploadBaseForm from "./useUploadBaseForm";

import {
  initialAIFormData,
  validateAIStep,
} from "../../utils/uploadProductScreen/validateUploadStep";

export default function useUploadAIForm(options = {}) {
  return useUploadBaseForm({
    draftKey: "ai_drafts",
    validateStep: validateAIStep,
    initialData: initialAIFormData,
    editData: options.editData,
    editImages: options.editImages,
    editFiles: options.editFiles,
    editTags: options.editTags,
    stepsConfig: [
      { id: 1, name: "Thông tin mô hình", icon: "🤖" },
      { id: 2, name: "Dataset", icon: "📁" },
      { id: 3, name: "Xuất bản", icon: "🚀" },
    ],
  });
}
