# NoobIDE
<img width="1366" height="685" alt="screenshot" src="https://github.com/user-attachments/assets/d826d3d8-4cf1-4ea8-b131-b5692bdb0132" />

> ⚠️ **Disclaimer for Beginners**
> **NoobIDE** and the **Noob** language were created purely as a fun, educational toy project for beginners. It has no JIT compiler, no advanced garbage collection, and no complex error handling. It is designed to be the simplest version of a scripting language possible. **Please do not take this project seriously or use it in production!**

---

**NoobIDE** is a lightweight, single-file web-based IDE for building and running **Noob** scripts (`.noob`) alongside web files (`HTML`, `CSS`, `JS`).

---

## 🚀 Features

- **Ultra-Simple `.noob` Interpreter** – Powered by a custom minimal lexer, parser, and interpreter.
- **No Parentheses (`()`) & No Double Quotes (`"`)** – Function calls and control blocks don't require parentheses, and strings only use single quotes (`'`) or backticks (`` ` ``).
- **Minimal Keyword Set** – Stripped down to just 6 essential keywords (`set`, `if`, `loop`, `return`, `true`, `false`).
- **Monaco Editor Integration** – Code editing with custom syntax highlighting tailored specifically for `.noob`.
- **Project Explorer** – Full workspace management (create, rename, copy, delete files/folders, and download ZIPs).
- **Real-Time Web View** – Live split-screen preview for HTML, CSS, JS, and `renderWeb` virtual renders.
- **Integrated Terminal & Logs** – Live command output, interactive input prompts (`ask`), and execution logs.

---

## 🔤 Syntax Overview

- **Keywords**: `set`, `if`, `loop`, `return`, `true`, `false`
- **Quotes**: Only `'single quotes'` and `` `backticks` `` are supported.
- **No Parentheses**: Commands are called cleanly using space/comma separated parameters.
- **Built-in Functions**:
  - `yo msg` – Print output to the terminal log.
  - `ask prompt` – Request interactive input from the user in the terminal.
  - `rng min, max` – Generate a random integer between min and max.
  - `len item` – Get the length of a string, array, or map.
  - `renderWeb html, css, js` – Render web content directly to the live Web View.
  - `fileRead filename` / `fileWrite filename, content` – Read and write workspace files.

---

## 💻 Quick Start

1. Place `index.php` on any server running **PHP 7.4+** (with the Zip extension enabled for ZIP downloads).
2. Open `http://your-server/index.php` in your browser.
3. Start editing `workspace/main.noob` and click **Run Program**!

---

## 📜 Example `.noob` Code

```noob
set html = '<h1>Hello World!</h1>'
set css = 'h1 { color: #4b6eaf; font-family: sans-serif; }'
set js = 'console.log(`App loaded!`);'

renderWeb html, css, js
yo 'NoobIDE is ready!'

# Simple control structures
set count = 3
loop count {
  yo 'Looping...'
}

if count == 3 {
  yo 'Count is exactly 3!'
}
