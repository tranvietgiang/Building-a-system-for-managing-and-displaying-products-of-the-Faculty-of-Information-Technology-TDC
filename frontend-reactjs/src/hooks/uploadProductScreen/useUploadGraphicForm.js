import useUploadBaseForm from "./useUploadBaseForm";

import {
  initialGraphicFormData,
  validateGraphicStep,
} from "../../utils/uploadProductScreen/validateUploadStep";

export default function useUploadGraphicForm(options = {}) {
  return useUploadBaseForm({
    draftKey: "graphic_drafts",
    validateStep: validateGraphicStep,
    initialData: initialGraphicFormData,
    editData: options.editData,
    editImages: options.editImages,
    editFiles: options.editFiles,
    editTags: options.editTags,
    stepsConfig: [
      { id: 1, name: "Thiết kế", icon: "🎨" },
      { id: 2, name: "Mockup", icon: "🖼️" },
      { id: 3, name: "Hoàn tất", icon: "✅" },
    ],
  });
}
