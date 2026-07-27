# NoobIDE
<img width="1366" height="685" alt="screenshot" src="https://github.com/user-attachments/assets/d826d3d8-4cf1-4ea8-b131-b5692bdb0132" />


**NoobIDE** is a lightweight, single-file web-based IDE for building and running **Noob** scripts (`.noob`) alongside web files (`HTML`, `CSS`, `JS`).

---

## 🚀 Features

- **Monaco Editor Integration** – Code editing with custom syntax highlighting and auto-formatting.
- **Built-in `.noob` Interpreter** – Custom lexer, parser, and interpreter supporting logic, loops, functions, web rendering, and I/O.
- **Project Explorer** – Full workspace management (create, rename, copy, delete files/folders, and download ZIP).
- **Real-Time Web View** – Live split-screen preview for HTML, CSS, JS, and `renderWeb()` calls.
- **SQLite Database Support** – Query `.db` or `.sqlite` files directly using built-in functions.
- **Integrated Terminal & Logs** – Live command output, interactive input (`ask`), and error reporting.

---

## 💻 Quick Start

1. Place `index.php` on any server running **PHP 7.4+** with PDO SQLite and Zip extensions enabled.
2. Open `http://your-server/index.php` in your browser.
3. Start editing `workspace/main.noob` and click **Run Program**!

---

## 📜 Example `.noob` Code

```noob
set html = "<h1>Hello World!</h1>"
set css = "h1 { color: #4b6eaf; font-family: sans-serif; }"
set js = "console.log('App loaded!');"

renderWeb(html, css, js)
yo("NoobIDE is ready!")
