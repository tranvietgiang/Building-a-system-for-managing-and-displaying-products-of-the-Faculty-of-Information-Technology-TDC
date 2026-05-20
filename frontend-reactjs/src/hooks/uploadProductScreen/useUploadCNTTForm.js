import useUploadBaseForm from "./useUploadBaseForm";

import {
  initialCNTTFormData,
  validateCNTTStep,
} from "../../utils/uploadProductScreen/validateUploadStep";

export default function useUploadCNTTForm(options = {}) {
  return useUploadBaseForm({
    draftKey: "cntt_drafts",
    validateStep: validateCNTTStep,
    initialData: initialCNTTFormData,
    editData: options.editData,
    editImages: options.editImages,
    editFiles: options.editFiles,
    editTags: options.editTags,
    stepsConfig: [
      { id: 1, name: "Thông tin", icon: "📋" },
      { id: 2, name: "Media", icon: "🖼️" },
      { id: 3, name: "Hoàn tất", icon: "✅" },
    ],
  });
}
