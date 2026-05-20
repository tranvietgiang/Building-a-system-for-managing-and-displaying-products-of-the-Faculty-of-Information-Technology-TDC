// hooks/upload/useUploadNetworkForm.js
import useUploadBaseForm from "./useUploadBaseForm";
import {
  validateNetworkStep,
  initialNetworkFormData,
} from "../../utils/uploadProductScreen/validateUploadStep";

export default function useUploadNetworkForm(options = {}) {
  return useUploadBaseForm({
    draftKey: "network_drafts",
    validateStep: validateNetworkStep,
    initialData: initialNetworkFormData,
    editData: options.editData,
    editImages: options.editImages,
    editFiles: options.editFiles,
    editTags: options.editTags,
    stepsConfig: [
      { id: 1, name: "Thông tin hệ thống", icon: "🌐" },
      { id: 2, name: "Topology & File", icon: "📁" },
      { id: 3, name: "Hoàn tất", icon: "🚀" },
    ],
  });
}
