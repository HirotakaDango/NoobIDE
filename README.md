# NoobIDE
<img width="1366" height="685" alt="screenshot" src="https://github.com/user-attachments/assets/d826d3d8-4cf1-4ea8-b131-b5692bdb0132" />

> ⚠️ **Disclaimer**
> **NoobIDE** and the **Noob** language have evolved into a structured, assembly-like scripting language. Featuring a **JIT Compiler**, **Garbage Collection**, and **Proper Error Handling**, it enforces strict vertical coding patterns and elegant 3-letter keywords.

---

**NoobIDE** is a lightweight, single-file web-based IDE for building and running **Noob** scripts (`.noob`) alongside web files (`HTML`, `CSS`, `JS`). 

---

## 🚀 Features

- **Vertical Assembly-Style Syntax** – Clean, linear code without deep nested pyramids. Block structures are elegantly terminated vertically using `end`.
- **Strictly No Parentheses Allowed** – Parentheses `()` and brackets `[]` have been completely banned. Logic relies on words and clean line parsing.
- **3-Letter Keyword Standard** – Consistent and minimalist. (`var`, `fun`, `ret`, `cls`, `new`, `ths`, etc.)
- **Natural Logic Operators** – Use plain English for mathematical comparisons (`greater than` and `less than`).
- **Blazing Fast JIT Compiler** – Compiles your `.noob` Abstract Syntax Tree (AST) directly into asynchronous JavaScript.
- **Object-Oriented** – Full support for classes, methods, and instantiation without the boilerplate.
- **Monaco Editor Integration** – Custom syntax highlighting that officially recognizes the new syntax.
- **Real-Time Web View** – Live split-screen preview for your web files and virtual renders.
- **Integrated Terminal** – Live command output and interactive input (`ask`).

---

## 🧠 Language Reference (Wiki)

### Variables & Data Types
- `var` : Declare a variable.
- `~` : String definition (turns the entire rest of the line into a string).
- `'` : Standard inline string (single quotes only).
- `tru` : True boolean.
- `fls` : False boolean.
- `nul` : Null value.

### Control Flow
- `if` : Conditional if.
- `els` : Conditional else.
- `whl` : While loop.
- `rpt` : Repeat loop (runs a specific number of times).
- `end` : Terminates a block (if statements, loops, functions, classes).
- `ret` : Return a value from a function.

### Operators & Logic
- `and` : Logical AND.
- `orr` : Logical OR.
- `not` : Logical NOT.
- `greater than` : Greater than comparison (`>`).
- `less than` : Less than comparison (`<`).

### Object-Oriented (OOP)
- `cls` : Define a class.
- `fun` : Define a function or class method.
- `new` : Instantiate an object.
- `ths` : Reference the current object instance (this).

### Built-in Functions
- `yo` : Console log. Prints to the terminal. Supports multiple arguments separated by commas.
- `ask` : Prompts the user for input in the terminal.
- `rng` : Generates a random number.
- `len` : Gets the length of a string or object.
- `rWb` : Renders HTML, CSS, and JS directly to the Web View frame (`renderWeb`).

---

## 💻 Quick Start

1. Place `index.php` on any server running **PHP 7.4+** (with the Zip extension enabled for ZIP downloads).
2. Open `http://your-server/index.php` in your browser.
3. Start editing `workspace/main.noob` and click **Run Program**!

---

## 📜 Example `.noob` Code

```noob
var name = ~ John
var age = 28

yo name, age

var html = ~ <h1><noob>yo name</noob></h1>
var css = ~ h1 { color: red; }
var js = ~ console.log(<noob>yo 'Hello virtually!'</noob>);

rWb html, css, js

cls Person
  fun init name
    ths.name = name
  end
  fun greet
    ret 'Hello from ' + ths.name
  end
end

var p = new Person 'Jane'
yo p.greet

fun check age
  if age greater than 18
    ret 'Adult'
  els
    ret 'Minor'
  end
end

yo 'Status: ' + check 20

var count = 0
whl count less than 3
  count += 1
  yo 'Count: ' + count
end