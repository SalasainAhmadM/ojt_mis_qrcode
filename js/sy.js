const now = new Date(
    new Intl.DateTimeFormat("en-US", {
      timeZone: "Asia/Manila",
      year: "numeric",
      month: "numeric",
      day: "numeric",
    }).format(new Date())
  );

  const syElement = document.getElementById("sy-text");
  const year = now.getFullYear();
  const month = now.getMonth() + 1;

  // Determine the school year
  if (month <= 5) {
    syElement.textContent = `S.Y. ${year - 1}-${year}`;
  } else {
    syElement.textContent = `S.Y. ${year}-${year + 1}`;
  }