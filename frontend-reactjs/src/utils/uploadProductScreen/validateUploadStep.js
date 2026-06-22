// utils/uploadProductScreen/validateUploadStep.js

/* =========================================================
   INITIAL FORM DATA
========================================================= */
export const initialCNTTFormData = {
  // chung
  title: "",
  description: "",
  cate_id: "",
  awards: "",
  github_link: "",
  demo_link: "",

  // riêng CNTT
  programming_language: "",
  framework: "",
  database_used: "",
};

export const initialGraphicFormData = {
  // chung
  title: "",
  description: "",
  cate_id: "",
  awards: "",

  // riêng đồ họa
  design_type: "",
  tools_used: "",
  behance_link: "",
  drive_link: "",
};

export const initialAIFormData = {
  // chung
  title: "",
  description: "",
  cate_id: "",
  awards: "",
  github_link: "",
  demo_link: "",

  // riêng AI
  model_used: "",
  framework: "",
  language: "",
  dataset_used: "",
  accuracy_score: "",
};

export const initialNetworkFormData = {
  // chung
  title: "",
  description: "",
  cate_id: "",
  awards: "",

  // riêng mạng
  simulation_tool: "",
  network_protocol: "",
  topology_type: "",
  config_file: "",
};

/* ================= COMMON ================= */

const isValidUrl = (value) => {
  try {
    new URL(value);
    return true;
  } catch {
    return false;
  }
};

const validateBasicInfo = (formData, errors) => {
  const description = formData.description || "";

  // TITLE
  if (!formData.title?.trim()) {
    errors.title = "Nhập tên sản phẩm";
  } else if (formData.title.length < 5) {
    errors.title = "Tên ≥ 5 ký tự";
  } else if (formData.title.length > 100) {
    errors.title = "Tên ≤ 100 ký tự";
  }

  // DESCRIPTION
  if (description.trim() && description.length < 10) {
    errors.description = "Mô tả ≥ 10 ký tự";
  } else if (description.length > 300) {
    errors.description = "Mô tả ≤ 300 ký tự";
  }

  // CATEGORY
  if (!formData.cate_id) {
    errors.cate_id = "Chọn danh mục";
  }

  // LINKS
  if (formData.github_link && !isValidUrl(formData.github_link)) {
    errors.github_link = "Link GitHub sai";
  }

  if (formData.demo_link && !isValidUrl(formData.demo_link)) {
    errors.demo_link = "Link demo sai";
  }
};

const validateMedia = (images = [], errors) => {
  if (images.length === 0) {
    errors.images = "Cần ít nhất 1 ảnh";
  }
  if (images.length > 10) {
    errors.images = "Tối đa 10 ảnh";
  }
};

/* ================= CNTT ================= */
export const validateCNTTStep = ({
  step,
  formData,
  images = [],
}) => {
  const errors = {};

  if (step === 1) validateBasicInfo(formData, errors);
  if (step === 2) validateMedia(images, errors);

  if (step === 3) {
    if (!formData.programming_language?.trim()) {
      errors.programming_language = "Nhập ngôn ngữ";
    } else if (formData.programming_language.length > 50) {
      errors.programming_language = "≤ 50 ký tự";
    }

    if (!formData.framework?.trim()) {
      errors.framework = "Nhập framework";
    } else if (formData.framework.length > 100) {
      errors.framework = "≤ 100 ký tự";
    }

    if (!formData.database_used?.trim()) {
      errors.database_used = "Nhập database";
    } else if (formData.database_used.length > 100) {
      errors.database_used = "≤ 100 ký tự";
    }
  }

  return errors;
};

/* ================= ĐỒ HỌA ================= */
export const validateGraphicStep = ({
  step,
  formData,
  images = [],
}) => {
  const errors = {};

  if (step === 1) {
    validateBasicInfo(formData, errors);

    if (!formData.design_type?.trim()) {
      errors.design_type = "Chọn loại thiết kế";
    } else if (formData.design_type.length > 50) {
      errors.design_type = "≤ 50 ký tự";
    }

    if (!formData.tools_used?.trim()) {
      errors.tools_used = "Nhập công cụ";
    } else if (formData.tools_used.length > 150) {
      errors.tools_used = "≤ 150 ký tự";
    }

    if (formData.behance_link && !isValidUrl(formData.behance_link)) {
      errors.behance_link = "Link sai";
    }

    if (formData.drive_link && !isValidUrl(formData.drive_link)) {
      errors.drive_link = "Link sai";
    }
  }

  if (step === 2) validateMedia(images, errors);

  return errors;
};

/* ================= AI ================= */
export const validateAIStep = ({ step, formData, images = [] }) => {
  const errors = {};

  if (step === 1) {
    validateBasicInfo(formData, errors);

    if (!formData.model_used?.trim()) {
      errors.model_used = "Nhập mô hình hoặc thuật toán sử dụng";
    } else if (formData.model_used.length > 100) {
      errors.model_used = "≤ 100 ký tự";
    }

  }

  if (step === 2) validateMedia(images, errors);

  if (step === 3) {
    if (!formData.framework?.trim()) {
      errors.framework = "Nhập framework";
    } else if (formData.framework.length > 100) {
      errors.framework = "≤ 100 ký tự";
    }

    if (!formData.dataset_used?.trim()) {
      errors.dataset_used = "Nhập dataset";
    } else if (formData.dataset_used.length > 100) {
      errors.dataset_used = "≤ 100 ký tự";
    }

    if (formData.language && formData.language.length > 50) {
      errors.language = "≤ 50 ký tự";
    }

    const acc = formData.accuracy_score;
    if (acc !== "" && acc !== null) {
      const num = Number(acc);
      if (isNaN(num)) {
        errors.accuracy_score = "Phải là số";
      } else if (num < 0) {
        errors.accuracy_score = "≥ 0";
      } else if (num > 100) {
        errors.accuracy_score = "≤ 100";
      }
    }
  }

  return errors;
};

/* ================= NETWORK ================= */
export const validateNetworkStep = ({
  step,
  formData,
  images = [],
}) => {
  const errors = {};

  if (step === 1) {
    validateBasicInfo(formData, errors);

    // ✅ Giao thức mạng
    if (!formData.network_protocol?.trim()) {
      errors.network_protocol = "Nhập giao thức mạng";
    } else if (formData.network_protocol.length > 100) {
      errors.network_protocol = "≤ 100 ký tự";
    }

    // ✅ Topology
    if (!formData.topology_type?.trim()) {
      errors.topology_type = "Nhập topology";
    } else if (formData.topology_type.length > 50) {
      errors.topology_type = "≤ 50 ký tự";
    }
  }

  if (step === 2) {
    validateMedia(images, errors);
  }

  if (step === 3) {
    // ✅ Tool
    if (!formData.simulation_tool?.trim()) {
      errors.simulation_tool = "Nhập công cụ mô phỏng";
    } else if (formData.simulation_tool.length > 100) {
      errors.simulation_tool = "≤ 100 ký tự";
    }
  }

  return errors;
};
