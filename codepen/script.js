document.getElementById("toggleMode").addEventListener("click", () => {
    document.body.classList.toggle("light");
});

document.getElementById("reset").addEventListener("click", () => {
    localStorage.clear();
    resetAll();
});

const html = document.getElementById("html");
const css = document.getElementById("css");
const iframe = document.querySelector("iframe");

function updatePreview() {
    const content = `
         <html>
            <head>
               <style>${css.value}</style>
            </head>
            <body>${html.value}</body>
         </html>
      `;
    const preview = iframe.contentDocument || iframe.contentWindow.document;
    preview.open();
    preview.write(content);
    preview.close();
}

html.addEventListener("input", updatePreview);
css.addEventListener("input", updatePreview);

// Initial load
updatePreview();