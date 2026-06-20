export const shareVisitorProduct = async ({ title, description, url }) => {
  if (navigator.share) {
    await navigator.share({
      title,
      text: description,
      url,
    });
    return true;
  }

  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(url);
    return true;
  }

  const input = document.createElement("textarea");
  input.value = url;
  input.setAttribute("readonly", "");
  input.style.position = "fixed";
  input.style.opacity = "0";
  document.body.appendChild(input);
  input.select();
  const copied = document.execCommand("copy");
  document.body.removeChild(input);

  return copied;
};
