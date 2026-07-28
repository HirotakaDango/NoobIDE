<?php
error_reporting(0);
$workspace_dir = __DIR__ . '/workspace';
if (!is_dir($workspace_dir)) {
  mkdir($workspace_dir, 0777, true);
}
file_put_contents(
  $workspace_dir . '/main.noob',
  "var name = ~ John\nvar age = 28\n\nyo name, age\n\nvar html = ~ <h1><noob>yo name</noob></h1>\nvar css = ~ h1 { color: red; }\nvar js = ~ console.log(<noob>yo 'Hello virtually!'</noob>);\n\nrWb html, css, js\n\ncls Person\n  fun init name\n    ths.name = name\n  end\n  fun greet\n    ret 'Hello from ' + ths.name\n  end\nend\n\nvar p = new Person 'Jane'\nyo p.greet"
);
function getSafePath($base, $path) {
  $path = str_replace(['..', '\\'], ['', '/'], $path);
  return rtrim($base, '/') . '/' . ltrim($path, '/');
}
function deleteDir($dirPath) {
  if (!is_dir($dirPath)) {
    unlink($dirPath);
    return;
  }
  if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
    $dirPath .= '/';
  }
  $files = glob($dirPath . '*', GLOB_MARK);
  foreach ($files as $file) {
    if (is_dir($file)) {
      deleteDir($file);
    } else {
      unlink($file);
    }
  }
  rmdir($dirPath);
}
function rcopy($src, $dst) {
  $dir = opendir($src);
  @mkdir($dst);
  while (($file = readdir($dir)) !== false) {
    if ($file != '.' && $file != '..') {
      if (is_dir($src . '/' . $file)) {
        rcopy($src . '/' . $file, $dst . '/' . $file);
      } else {
        copy($src . '/' . $file, $dst . '/' . $file);
      }
    }
  }
  closedir($dir);
}
function buildTree($dir, $baseDir) {
  $result = [];
  $items = array_diff(scandir($dir), ['.', '..']);
  foreach ($items as $item) {
    $path = $dir . '/' . $item;
    $rel = str_replace($baseDir . '/', '', $path);
    if (is_dir($path)) {
      $result[] = [
        'type' => 'folder',
        'name' => $item,
        'path' => $rel,
        'children' => buildTree($path, $baseDir),
      ];
    } else {
      $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
      if (in_array($ext, ['noob', 'html', 'css', 'js', 'txt'])) {
        $result[] = ['type' => 'file', 'name' => $item, 'path' => $rel];
      }
    }
  }
  return $result;
}
if (isset($_GET['download'])) {
  $path = getSafePath($workspace_dir, $_GET['download']);
  if (is_dir($path)) {
    $zipFile = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive();
    $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($files as $name => $file) {
      if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen(realpath($path)) + 1);
        $zip->addFile($filePath, $relativePath);
      }
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($path) . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);
    unlink($zipFile);
    exit();
  } elseif (file_exists($path)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit();
  }
}
if (isset($_GET['view'])) {
  $path = getSafePath($workspace_dir, $_GET['view']);
  if (file_exists($path) && !is_dir($path)) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $dir = dirname($path);
    if ($ext === 'html') {
      $content = file_get_contents($path);
      $cssPath = $dir . '/style.css';
      $jsPath = $dir . '/script.js';
      $css = file_exists($cssPath) ? file_get_contents($cssPath) : '';
      $js = file_exists($jsPath) ? file_get_contents($jsPath) : '';
      header('Content-Type: text/html');
      $inject = '';
      if ($css !== '') {
        $inject .= "\n<style>\n" . $css . "\n</style>";
      }
      if ($js !== '') {
        $inject .= "\n<script>\n" . $js . "\n</script>";
      }
      if ($inject !== '') {
        if (strpos($content, '</head>') !== false) {
          echo str_replace('</head>', $inject . "\n</head>", $content);
        } else {
          echo $content . $inject;
        }
      } else {
        echo $content;
      }
      exit();
    } elseif ($ext === 'noob') {
      $content = file_get_contents($path);
      preg_match("/var\s+html\s*=\s*~\s*(.*)/i", $content, $mHtml);
      preg_match("/var\s+css\s*=\s*~\s*(.*)/i", $content, $mCss);
      preg_match("/var\s+js\s*=\s*~\s*(.*)/i", $content, $mJs);
      $html = $mHtml[1] ?? '';
      $css = $mCss[1] ?? '';
      $js = $mJs[1] ?? '';
      header('Content-Type: text/html');
      echo "<!DOCTYPE html>\n<html>\n<head>\n";
      if ($css !== '') {
        echo "  <style>\n" . $css . "\n  </style>\n";
      }
      echo "</head>\n<body>\n" . $html . "\n";
      if ($js !== '') {
        echo "  <script>\n" . $js . "\n  </script>\n";
      }
      echo "</body>\n</html>";
      exit();
    } elseif ($ext === 'css') {
      header('Content-Type: text/css');
      readfile($path);
      exit();
    } elseif ($ext === 'js') {
      header('Content-Type: application/javascript');
      readfile($path);
      exit();
    } elseif ($ext === 'json') {
      header('Content-Type: application/json');
      readfile($path);
      exit();
    } else {
      header('Content-Type: text/plain');
      readfile($path);
      exit();
    }
  }
  die('File not found');
}
class NoobTokenType {
  const T_EOF = 'EOF';
  const T_NEWLINE = 'NEWLINE';
  const T_NUMBER = 'NUMBER';
  const T_STRING = 'STRING';
  const T_IDENTIFIER = 'IDENTIFIER';
  const T_KEYWORD = 'KEYWORD';
  const T_ASSIGN = '=';
  const T_PLUS_ASSIGN = '+=';
  const T_MINUS_ASSIGN = '-=';
  const T_PLUS = '+';
  const T_MINUS = '-';
  const T_MUL = '*';
  const T_DIV = '/';
  const T_MOD = '%';
  const T_POW = '^';
  const T_EQUAL = '==';
  const T_NOT_EQUAL = '!=';
  const T_GT = 'greater than';
  const T_LT = 'less than';
  const T_LBRACKET = '[';
  const T_RBRACKET = ']';
  const T_LBRACE = '{';
  const T_RBRACE = '}';
  const T_COMMA = ',';
  const T_DOT = '.';
}
class NoobToken {
  public $type;
  public $value;
  public $line;
  public function __construct($type, $value, $line = 1) {
    $this->type = $type;
    $this->value = $value;
    $this->line = $line;
  }
}
class NoobLexer {
  private $code;
  private $length;
  private $cursor = 0;
  private $line = 1;
  private $keywords = [
    'var' => true,
    'fun' => true,
    'ret' => true,
    'cls' => true,
    'new' => true,
    'ths' => true,
    'tru' => true,
    'fls' => true,
    'nul' => true,
    'whl' => true,
    'rpt' => true,
    'and' => true,
    'orr' => true,
    'not' => true,
    'if' => true,
    'els' => true,
    'end' => true,
    'yo' => true,
    'ask' => true,
    'rng' => true,
    'len' => true,
    'rWb' => true,
  ];
  public function __construct($sourceCode) {
    $this->code = $sourceCode;
    $this->length = strlen($sourceCode);
  }
  private function peek($ahead = 1) {
    if ($this->cursor + $ahead >= $this->length) {
      return null;
    }
    return $this->code[$this->cursor + $ahead];
  }
  public function tokenize() {
    $tokens = [];
    while ($this->cursor < $this->length) {
      $char = $this->code[$this->cursor];
      if ($char === ' ' || $char === "\t" || $char === "\r") {
        $this->cursor++;
        continue;
      }
      if ($char === "\n") {
        $this->line++;
        if (empty($tokens) || end($tokens)->type !== NoobTokenType::T_NEWLINE) {
          $tokens[] = new NoobToken(NoobTokenType::T_NEWLINE, "\n", $this->line);
        }
        $this->cursor++;
        continue;
      }
      $rem = substr($this->code, $this->cursor);
      if (strpos($rem, 'greater than') === 0 && ($this->cursor + 12 === $this->length || !ctype_alpha($this->code[$this->cursor + 12]))) {
        $tokens[] = new NoobToken(NoobTokenType::T_GT, 'greater than', $this->line);
        $this->cursor += 12;
        continue;
      }
      if (strpos($rem, 'less than') === 0 && ($this->cursor + 9 === $this->length || !ctype_alpha($this->code[$this->cursor + 9]))) {
        $tokens[] = new NoobToken(NoobTokenType::T_LT, 'less than', $this->line);
        $this->cursor += 9;
        continue;
      }
      if ($char === "'") {
        $strVal = '';
        $this->cursor++;
        while ($this->cursor < $this->length && $this->code[$this->cursor] !== "'") {
          if ($this->code[$this->cursor] === "\n") $this->line++;
          if ($this->code[$this->cursor] === '\\' && $this->peek() === "'") $this->cursor++;
          $strVal .= $this->code[$this->cursor];
          $this->cursor++;
        }
        $this->cursor++;
        $tokens[] = new NoobToken(NoobTokenType::T_STRING, $strVal, $this->line);
        continue;
      }
      if ($char === '~') {
        $strVal = '';
        $this->cursor++;
        if ($this->cursor < $this->length && $this->code[$this->cursor] === ' ') {
          $this->cursor++;
        }
        while ($this->cursor < $this->length && $this->code[$this->cursor] !== "\n") {
          $strVal .= $this->code[$this->cursor];
          $this->cursor++;
        }
        $tokens[] = new NoobToken(NoobTokenType::T_STRING, rtrim($strVal), $this->line);
        continue;
      }
      if (ctype_digit($char)) {
        $numVal = '';
        while ($this->cursor < $this->length && (ctype_digit($this->code[$this->cursor]) || $this->code[$this->cursor] === '.')) {
          $numVal .= $this->code[$this->cursor];
          $this->cursor++;
        }
        $val = strpos($numVal, '.') !== false ? (float) $numVal : (int) $numVal;
        $tokens[] = new NoobToken(NoobTokenType::T_NUMBER, $val, $this->line);
        continue;
      }
      if (ctype_alpha($char) || $char === '_') {
        $ident = '';
        while ($this->cursor < $this->length && (ctype_alnum($this->code[$this->cursor]) || $this->code[$this->cursor] === '_')) {
          $ident .= $this->code[$this->cursor];
          $this->cursor++;
        }
        $type = isset($this->keywords[$ident]) ? NoobTokenType::T_KEYWORD : NoobTokenType::T_IDENTIFIER;
        $tokens[] = new NoobToken($type, $ident, $this->line);
        continue;
      }
      $next = $this->peek();
      $two = $char . $next;
      if ($two === '==') {
        $tokens[] = new NoobToken(NoobTokenType::T_EQUAL, '==', $this->line);
        $this->cursor += 2;
        continue;
      }
      if ($two === '!=') {
        $tokens[] = new NoobToken(NoobTokenType::T_NOT_EQUAL, '!=', $this->line);
        $this->cursor += 2;
        continue;
      }
      if ($two === '+=') {
        $tokens[] = new NoobToken(NoobTokenType::T_PLUS_ASSIGN, '+=', $this->line);
        $this->cursor += 2;
        continue;
      }
      if ($two === '-=') {
        $tokens[] = new NoobToken(NoobTokenType::T_MINUS_ASSIGN, '-=', $this->line);
        $this->cursor += 2;
        continue;
      }
      switch ($char) {
        case '=':
          $tokens[] = new NoobToken(NoobTokenType::T_ASSIGN, '=', $this->line);
          break;
        case '+':
          $tokens[] = new NoobToken(NoobTokenType::T_PLUS, '+', $this->line);
          break;
        case '-':
          $tokens[] = new NoobToken(NoobTokenType::T_MINUS, '-', $this->line);
          break;
        case '*':
          $tokens[] = new NoobToken(NoobTokenType::T_MUL, '*', $this->line);
          break;
        case '/':
          $tokens[] = new NoobToken(NoobTokenType::T_DIV, '/', $this->line);
          break;
        case '%':
          $tokens[] = new NoobToken(NoobTokenType::T_MOD, '%', $this->line);
          break;
        case '^':
          $tokens[] = new NoobToken(NoobTokenType::T_POW, '^', $this->line);
          break;
        case ',':
          $tokens[] = new NoobToken(NoobTokenType::T_COMMA, ',', $this->line);
          break;
        case '.':
          $tokens[] = new NoobToken(NoobTokenType::T_DOT, '.', $this->line);
          break;
        case '[':
          $tokens[] = new NoobToken(NoobTokenType::T_LBRACKET, '[', $this->line);
          break;
        case ']':
          $tokens[] = new NoobToken(NoobTokenType::T_RBRACKET, ']', $this->line);
          break;
        case '{':
          $tokens[] = new NoobToken(NoobTokenType::T_LBRACE, '{', $this->line);
          break;
        case '}':
          $tokens[] = new NoobToken(NoobTokenType::T_RBRACE, '}', $this->line);
          break;
        case '(': case ')':
          throw new Exception("Syntax Error: Parentheses are completely banned at line {$this->line}");
        case '<': case '>':
          throw new Exception("Syntax Error: Use 'less than' and 'greater than' instead of '<' and '>' at line {$this->line}");
        default:
          throw new Exception("Syntax Error: Unknown token '{$char}' at line {$this->line}");
      }
      $this->cursor++;
    }
    $tokens[] = new NoobToken(NoobTokenType::T_EOF, null, $this->line);
    return $tokens;
  }
}
abstract class ASTNode {}
class VarDeclNode extends ASTNode {
  public $varDeclName;
  public $varDeclExpr;
  public function __construct($name, $expr) {
    $this->varDeclName = $name;
    $this->varDeclExpr = $expr;
  }
}
class VarAssignNode extends ASTNode {
  public $assignTarget;
  public $assignOp;
  public $assignExpr;
  public function __construct($target, $op, $expr) {
    $this->assignTarget = $target;
    $this->assignOp = $op;
    $this->assignExpr = $expr;
  }
}
class CallNode extends ASTNode {
  public $callFuncExpr;
  public $callArgs;
  public function __construct($func, $args = []) {
    $this->callFuncExpr = $func;
    $this->callArgs = $args;
  }
}
class FunctionDeclNode extends ASTNode {
  public $funcName;
  public $funcParams;
  public $funcBody;
  public function __construct($name, $params, $body) {
    $this->funcName = $name;
    $this->funcParams = $params;
    $this->funcBody = $body;
  }
}
class ReturnNode extends ASTNode {
  public $returnValueExpr;
  public $isReturn = true;
  public function __construct($expr = null) {
    $this->returnValueExpr = $expr;
  }
}
class IfNode extends ASTNode {
  public $ifCond;
  public $thenBlock;
  public $elseBlock;
  public function __construct($cond, $then, $else = null) {
    $this->ifCond = $cond;
    $this->thenBlock = $then;
    $this->elseBlock = $else;
  }
}
class RepeatNode extends ASTNode {
  public $repeatCount;
  public $repeatBody;
  public function __construct($count, $body) {
    $this->repeatCount = $count;
    $this->repeatBody = $body;
  }
}
class WhileNode extends ASTNode {
  public $whileCond;
  public $whileBody;
  public function __construct($cond, $body) {
    $this->whileCond = $cond;
    $this->whileBody = $body;
  }
}
class BinaryOpNode extends ASTNode {
  public $binLeft;
  public $binOp;
  public $binRight;
  public function __construct($l, $o, $r) {
    $this->binLeft = $l;
    $this->binOp = $o;
    $this->binRight = $r;
  }
}
class UnaryOpNode extends ASTNode {
  public $unOp;
  public $unOperand;
  public function __construct($o, $opnd) {
    $this->unOp = $o;
    $this->unOperand = $opnd;
  }
}
class ArrayNode extends ASTNode {
  public $arrElements;
  public function __construct($els = []) {
    $this->arrElements = $els;
  }
}
class MapNode extends ASTNode {
  public $mapEntries;
  public function __construct($ents = []) {
    $this->mapEntries = $ents;
  }
}
class IndexAccessNode extends ASTNode {
  public $idxTarget;
  public $idxIndex;
  public function __construct($tgt, $idx) {
    $this->idxTarget = $tgt;
    $this->idxIndex = $idx;
  }
}
class MemberAccessNode extends ASTNode {
  public $memTarget;
  public $memMember;
  public function __construct($tgt, $mem) {
    $this->memTarget = $tgt;
    $this->memMember = $mem;
  }
}
class LiteralNode extends ASTNode {
  public $litValue;
  public $isLiteral = true;
  public function __construct($val) {
    $this->litValue = $val;
  }
}
class VarRefNode extends ASTNode {
  public $varRefName;
  public function __construct($n) {
    $this->varRefName = $n;
  }
}
class BlockNode extends ASTNode {
  public $blockStatements;
  public function __construct($stmts = []) {
    $this->blockStatements = $stmts;
  }
}
class ClassDeclNode extends ASTNode {
  public $className;
  public $classMethods;
  public function __construct($n, $m) {
    $this->className = $n;
    $this->classMethods = $m;
  }
}
class MethodDeclNode extends ASTNode {
  public $methodName;
  public $methodParams;
  public $methodBody;
  public function __construct($n, $p, $b) {
    $this->methodName = $n;
    $this->methodParams = $p;
    $this->methodBody = $b;
  }
}
class NewNode extends ASTNode {
  public $newClassName;
  public $newArgs;
  public function __construct($n, $a) {
    $this->newClassName = $n;
    $this->newArgs = $a;
  }
}
class ThisNode extends ASTNode {
  public $isThis = true;
}
class NoobParser {
  private $tokens;
  private $current = 0;
  public function __construct(array $tokens) {
    $this->tokens = $tokens;
  }
  private function peek($ahead = 0) {
    $idx = $this->current + $ahead;
    if ($idx >= count($this->tokens)) {
      return $this->tokens[count($this->tokens) - 1];
    }
    return $this->tokens[$idx];
  }
  private function advance() {
    return $this->tokens[$this->current++];
  }
  private function match($type, $val = null) {
    $token = $this->peek();
    if ($token->type === $type) {
      if ($val !== null && $token->value !== $val) {
        return false;
      }
      $this->advance();
      return true;
    }
    return false;
  }
  private function consume($type, $val = null, $errMsg = 'Unexpected token') {
    $token = $this->peek();
    if ($token->type === $type && ($val === null || $token->value === $val)) {
      return $this->advance();
    }
    throw new Exception("Parse Error [Line {$token->line}]: {$errMsg}. Found '{$token->value}'");
  }
  private function skipNewlines() {
    while ($this->peek()->type === NoobTokenType::T_NEWLINE) {
      $this->advance();
    }
  }
  public function parseProgram() {
    $statements = [];
    $this->skipNewlines();
    while ($this->peek()->type !== NoobTokenType::T_EOF) {
      $stmt = $this->parseStatement();
      if ($stmt !== null) $statements[] = $stmt;
      $this->skipNewlines();
    }
    return new BlockNode($statements);
  }
  private function parseBlock($stopTokens = []) {
    $statements = [];
    $this->skipNewlines();
    while ($this->peek()->type !== NoobTokenType::T_EOF) {
      if (in_array($this->peek()->value, $stopTokens)) {
        break;
      }
      $stmt = $this->parseStatement();
      if ($stmt !== null) $statements[] = $stmt;
      $this->skipNewlines();
    }
    return new BlockNode($statements);
  }
  private function parseStatement() {
    $this->skipNewlines();
    if ($this->peek()->type === NoobTokenType::T_EOF) return null;
    $token = $this->peek();
    if ($token->type === NoobTokenType::T_KEYWORD && $token->value === 'var') {
      $this->advance();
      $varName = $this->consume(NoobTokenType::T_IDENTIFIER, null, 'Expected variable name')->value;
      $this->consume(NoobTokenType::T_ASSIGN, '=', "Expected '='");
      $expr = $this->parseExpression();
      return new VarDeclNode($varName, $expr);
    }
    if ($token->type === NoobTokenType::T_KEYWORD && $token->value === 'fun') {
      $this->advance();
      $fnName = $this->consume(NoobTokenType::T_IDENTIFIER, null, 'Expected function name')->value;
      $params = [];
      while ($this->peek()->type === NoobTokenType::T_IDENTIFIER) {
        $params[] = $this->advance()->value;
        if ($this->match(NoobTokenType::T_COMMA)) continue;
        break;
      }
      $this->skipNewlines();
      $body = $this->parseBlock(['end', 'fun', 'cls']);
      if ($this->peek()->value === 'end') $this->advance();
      return new FunctionDeclNode($fnName, $params, $body);
    }
    if ($token->type === NoobTokenType::T_KEYWORD && $token->value === 'cls') {
      $this->advance();
      $className = $this->consume(NoobTokenType::T_IDENTIFIER, null, 'Expected class name')->value;
      $this->skipNewlines();
      $methods = [];
      while ($this->peek()->type !== NoobTokenType::T_EOF && $this->peek()->value !== 'end') {
        if ($this->peek()->value === 'fun') {
          $this->advance();
          $fnName = $this->consume(NoobTokenType::T_IDENTIFIER, null, 'Expected method name')->value;
          $params = [];
          while ($this->peek()->type === NoobTokenType::T_IDENTIFIER) {
            $params[] = $this->advance()->value;
            if ($this->match(NoobTokenType::T_COMMA)) continue;
            break;
          }
          $this->skipNewlines();
          $body = $this->parseBlock(['end', 'fun']);
          if ($this->peek()->value === 'end') $this->advance();
          $methods[] = new MethodDeclNode($fnName, $params, $body);
        } else {
          $this->advance();
        }
        $this->skipNewlines();
      }
      if ($this->peek()->value === 'end') $this->advance();
      return new ClassDeclNode($className, $methods);
    }
    if ($token->type === NoobTokenType::T_KEYWORD && $token->value === 'ret') {
      $this->advance();
      $valExpr = null;
      if ($this->peek()->type !== NoobTokenType::T_NEWLINE && $this->peek()->type !== NoobTokenType::T_EOF) {
        $valExpr = $this->parseExpression();
      }
      return new ReturnNode($valExpr);
    }
    if ($token->type === NoobTokenType::T_KEYWORD && $token->value === 'if') {
      $this->advance();
      $cond = $this->parseExpression();
      $this->skipNewlines();
      $thenBlock = $this->parseBlock(['els', 'end', 'fun', 'cls']);
      $elseBlock = null;
      if ($this->peek()->type === NoobTokenType::T_KEYWORD && $this->peek()->value === 'els') {
        $this->advance();
        $this->skipNewlines();
        $elseBlock = $this->parseBlock(['end', 'fun', 'cls']);
      }
      if ($this->peek()->type === NoobTokenType::T_KEYWORD && $this->peek()->value === 'end') {
        $this->advance();
      }
      return new IfNode($cond, $thenBlock, $elseBlock);
    }
    if ($token->type === NoobTokenType::T_KEYWORD && $token->value === 'rpt') {
      $this->advance();
      $countExpr = $this->parseExpression();
      $this->skipNewlines();
      $body = $this->parseBlock(['end', 'fun', 'cls']);
      if ($this->peek()->value === 'end') $this->advance();
      return new RepeatNode($countExpr, $body);
    }
    if ($token->type === NoobTokenType::T_KEYWORD && $token->value === 'whl') {
      $this->advance();
      $condExpr = $this->parseExpression();
      $this->skipNewlines();
      $body = $this->parseBlock(['end', 'fun', 'cls']);
      if ($this->peek()->value === 'end') $this->advance();
      return new WhileNode($condExpr, $body);
    }
    $expr = $this->parseExpression();
    if (
      $this->peek()->type === NoobTokenType::T_ASSIGN ||
      $this->peek()->type === NoobTokenType::T_PLUS_ASSIGN ||
      $this->peek()->type === NoobTokenType::T_MINUS_ASSIGN
    ) {
      $opToken = $this->advance();
      $valExpr = $this->parseExpression();
      return new VarAssignNode($expr, $opToken->value, $valExpr);
    }
    return $expr;
  }
  private function parseExpression() {
    return $this->parseLogicalOr();
  }
  private function parseLogicalOr() {
    $left = $this->parseLogicalAnd();
    while ($this->peek()->type === NoobTokenType::T_KEYWORD && $this->peek()->value === 'orr') {
      $op = $this->advance()->value;
      $right = $this->parseLogicalAnd();
      $left = new BinaryOpNode($left, $op, $right);
    }
    return $left;
  }
  private function parseLogicalAnd() {
    $left = $this->parseEquality();
    while ($this->peek()->type === NoobTokenType::T_KEYWORD && $this->peek()->value === 'and') {
      $op = $this->advance()->value;
      $right = $this->parseEquality();
      $left = new BinaryOpNode($left, $op, $right);
    }
    return $left;
  }
  private function parseEquality() {
    $left = $this->parseComparison();
    while (in_array($this->peek()->type, [NoobTokenType::T_EQUAL, NoobTokenType::T_NOT_EQUAL])) {
      $op = $this->advance()->value;
      $right = $this->parseComparison();
      $left = new BinaryOpNode($left, $op, $right);
    }
    return $left;
  }
  private function parseComparison() {
    $left = $this->parseAdditive();
    while (in_array($this->peek()->type, [NoobTokenType::T_GT, NoobTokenType::T_LT])) {
      $op = $this->advance()->value;
      $right = $this->parseAdditive();
      $left = new BinaryOpNode($left, $op, $right);
    }
    return $left;
  }
  private function parseAdditive() {
    $left = $this->parseMultiplicative();
    while (in_array($this->peek()->type, [NoobTokenType::T_PLUS, NoobTokenType::T_MINUS])) {
      $op = $this->advance()->value;
      $right = $this->parseMultiplicative();
      $left = new BinaryOpNode($left, $op, $right);
    }
    return $left;
  }
  private function parseMultiplicative() {
    $left = $this->parsePower();
    while (in_array($this->peek()->type, [NoobTokenType::T_MUL, NoobTokenType::T_DIV, NoobTokenType::T_MOD])) {
      $op = $this->advance()->value;
      $right = $this->parsePower();
      $left = new BinaryOpNode($left, $op, $right);
    }
    return $left;
  }
  private function parsePower() {
    $left = $this->parseUnary();
    while ($this->peek()->type === NoobTokenType::T_POW) {
      $op = $this->advance()->value;
      $right = $this->parseUnary();
      $left = new BinaryOpNode($left, $op, $right);
    }
    return $left;
  }
  private function parseUnary() {
    if ($this->peek()->type === NoobTokenType::T_MINUS) {
      $this->advance();
      return new UnaryOpNode('-', $this->parseUnary());
    }
    if ($this->peek()->type === NoobTokenType::T_KEYWORD && $this->peek()->value === 'not') {
      $this->advance();
      return new UnaryOpNode('not', $this->parseUnary());
    }
    return $this->parseCallOrAccess();
  }
  private function isExprStart($type) {
    $val = $this->peek()->value;
    return in_array($type, [
      NoobTokenType::T_NUMBER,
      NoobTokenType::T_STRING,
      NoobTokenType::T_IDENTIFIER,
      NoobTokenType::T_LBRACKET,
      NoobTokenType::T_LBRACE,
      NoobTokenType::T_MINUS
    ]) || ($type === NoobTokenType::T_KEYWORD && in_array($val, ['tru', 'fls', 'nul', 'ths', 'new', 'not', 'yo', 'ask', 'rng', 'len', 'rWb']));
  }
  private function parseCallOrAccess() {
    $startToken = $this->peek();
    $line = $startToken->line;
    $expr = $this->parsePrimary();
    while (true) {
      if ($this->match(NoobTokenType::T_DOT)) {
        $memberToken = $this->consume(NoobTokenType::T_IDENTIFIER, null, 'Expected property name');
        $expr = new MemberAccessNode($expr, $memberToken->value);
      } else {
        $next = $this->peek();
        if ($next->line === $line && $this->isExprStart($next->type)) {
          $args = [];
          do {
            $args[] = $this->parseExpression();
          } while ($this->match(NoobTokenType::T_COMMA));
          $expr = new CallNode($expr, $args);
        } else {
          break;
        }
      }
    }
    return $expr;
  }
  private function parsePrimary() {
    $token = $this->peek();
    if ($token->type === NoobTokenType::T_NUMBER) {
      $this->advance();
      return new LiteralNode($token->value);
    }
    if ($token->type === NoobTokenType::T_STRING) {
      $this->advance();
      return new LiteralNode($token->value);
    }
    if ($token->type === NoobTokenType::T_KEYWORD) {
      $val = $token->value;
      if (in_array($val, ['tru', 'fls', 'nul'])) {
        $this->advance();
        return new LiteralNode($val === 'tru' ? true : ($val === 'fls' ? false : null));
      }
      if ($val === 'ths') {
        $this->advance();
        return new ThisNode();
      }
      if ($val === 'new') {
        $this->advance();
        $className = $this->consume(NoobTokenType::T_IDENTIFIER, null, 'Expected class name after new')->value;
        $args = [];
        $next = $this->peek();
        if ($next->line === $token->line && $this->isExprStart($next->type)) {
          do {
            $args[] = $this->parseExpression();
          } while ($this->match(NoobTokenType::T_COMMA));
        }
        return new NewNode($className, $args);
      }
      if (in_array($val, ['yo', 'ask', 'rng', 'len', 'rWb'])) {
        $this->advance();
        return new VarRefNode($val);
      }
    }
    if ($token->type === NoobTokenType::T_IDENTIFIER) {
      $name = $this->advance()->value;
      return new VarRefNode($name);
    }
    if ($token->type === NoobTokenType::T_LBRACKET) {
      $this->advance();
      $elements = [];
      $this->skipNewlines();
      if ($this->peek()->type !== NoobTokenType::T_RBRACKET) {
        do {
          $this->skipNewlines();
          $elements[] = $this->parseExpression();
          $this->skipNewlines();
        } while ($this->match(NoobTokenType::T_COMMA));
      }
      $this->consume(NoobTokenType::T_RBRACKET, ']', "Expected ']'");
      return new ArrayNode($elements);
    }
    if ($token->type === NoobTokenType::T_LBRACE) {
      $this->advance();
      $entries = [];
      $this->skipNewlines();
      if ($this->peek()->type !== NoobTokenType::T_RBRACE) {
        do {
          $this->skipNewlines();
          $keyToken = $this->peek();
          $key = '';
          if ($keyToken->type === NoobTokenType::T_IDENTIFIER || $keyToken->type === NoobTokenType::T_STRING) {
            $key = $this->advance()->value;
          } else {
            throw new Exception("Expected map key at line {$keyToken->line}");
          }
          $this->consume(NoobTokenType::T_COLON, ':', "Expected ':'");
          $entries[$key] = $this->parseExpression();
          $this->skipNewlines();
        } while ($this->match(NoobTokenType::T_COMMA));
      }
      $this->consume(NoobTokenType::T_RBRACE, '}', "Expected '}'");
      return new MapNode($entries);
    }
    throw new Exception("Parse Error [Line {$token->line}]: Unexpected token '{$token->value}'");
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  ob_start();
  header('Content-Type: application/json');
  try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
      throw new Exception('Invalid JSON payload');
    }
    $action = $data['action'] ?? '';
    if ($action === 'list') {
      $files = buildTree($workspace_dir, $workspace_dir);
      ob_clean();
      echo json_encode(['success' => true, 'files' => $files]);
      exit();
    }
    if ($action === 'read') {
      $path = getSafePath($workspace_dir, $data['filename'] ?? '');
      if (!file_exists($path)) {
        throw new Exception('File not found');
      }
      ob_clean();
      echo json_encode([
        'success' => true,
        'content' => file_get_contents($path),
      ]);
      exit();
    }
    if ($action === 'read_web') {
      $html = file_exists($workspace_dir . '/index.html') ? file_get_contents($workspace_dir . '/index.html') : '';
      $css = file_exists($workspace_dir . '/style.css') ? file_get_contents($workspace_dir . '/style.css') : '';
      $js = file_exists($workspace_dir . '/script.js') ? file_get_contents($workspace_dir . '/script.js') : '';
      ob_clean();
      echo json_encode([
        'success' => true,
        'html' => $html,
        'css' => $css,
        'js' => $js,
      ]);
      exit();
    }
    if ($action === 'write') {
      $path = getSafePath($workspace_dir, $data['filename'] ?? '');
      $ext = pathinfo($path, PATHINFO_EXTENSION);
      if (!in_array($ext, ['noob', 'html', 'css', 'js', 'txt'])) {
        throw new Exception('Invalid file extension');
      }
      $dir = dirname($path);
      if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
      }
      file_put_contents($path, $data['content'] ?? '');
      ob_clean();
      echo json_encode(['success' => true]);
      exit();
    }
    if ($action === 'create_folder') {
      $path = getSafePath($workspace_dir, $data['path'] ?? '');
      if (!is_dir($path)) {
        mkdir($path, 0777, true);
      }
      ob_clean();
      echo json_encode(['success' => true]);
      exit();
    }
    if ($action === 'rename') {
      $old = getSafePath($workspace_dir, $data['old'] ?? '');
      $new = getSafePath($workspace_dir, $data['new'] ?? '');
      if (file_exists($old)) {
        rename($old, $new);
      }
      ob_clean();
      echo json_encode(['success' => true]);
      exit();
    }
    if ($action === 'copy') {
      $old = getSafePath($workspace_dir, $data['old'] ?? '');
      $new = getSafePath($workspace_dir, $data['new'] ?? '');
      if (is_dir($old)) {
        rcopy($old, $new);
      } elseif (file_exists($old)) {
        copy($old, $new);
      }
      ob_clean();
      echo json_encode(['success' => true]);
      exit();
    }
    if ($action === 'delete') {
      $path = getSafePath($workspace_dir, $data['path'] ?? '');
      if (file_exists($path) || is_dir($path)) {
        deleteDir($path);
      }
      ob_clean();
      echo json_encode(['success' => true]);
      exit();
    }
    if ($action === 'execute') {
      $code = $data['code'] ?? '';
      $lexer = new NoobLexer($code);
      $tokens = $lexer->tokenize();
      $parser = new NoobParser($tokens);
      $ast = $parser->parseProgram();
      ob_clean();
      echo json_encode(['success' => true, 'ast' => $ast]);
      exit();
    }
    throw new Exception('Unknown action');
  } catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NoobIDE</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 128 128'%3E%3Crect width='128' height='128' rx='28' fill='%23212223'/%3E%3Crect x='4' y='4' width='120' height='120' rx='24' fill='none' stroke='%233c3f41' stroke-width='4'/%3E%3Cpath d='M 28 28 H 100 A 12 12 0 0 1 112 40 V 100 A 12 12 0 0 1 100 112 H 28 A 12 12 0 0 1 16 100 V 40 A 12 12 0 0 1 28 28 Z' fill='%232b2b2b' stroke='%234b6eaf' stroke-width='3'/%3E%3Cpath d='M 36 50 L 26 64 L 36 78' fill='none' stroke='%23cc7832' stroke-width='7' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M 48 78 V 50 L 80 78 V 50' fill='none' stroke='%23a9b7c6' stroke-width='8' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M 92 50 L 102 64 L 92 78' fill='none' stroke='%234b6eaf' stroke-width='7' stroke-linecap='round' stroke-linejoin='round'/%3E%3Crect x='50' y='86' width='28' height='5' fill='%236a8759' rx='2'/%3E%3C/svg%3E">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      :root {
        --java-bg: #3c3f41;
        --java-editor: #2b2b2b;
        --java-border: #242627;
        --java-text: #a9b7c6;
        --java-accent: #4b6eaf;
        --java-hover: #4e5254;
      }
      body {
        background-color: var(--java-bg);
        color: var(--java-text);
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      }
      .scrollbar-hide::-webkit-scrollbar {
        display: none;
      }
      .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
      }
      .terminal-line {
        min-height: 1.25rem;
        word-wrap: break-word;
      }
      .terminal-input-wrapper {
        display: flex;
        align-items: center;
        margin-top: 4px;
      }
      .terminal-input {
        background: transparent;
        border: none;
        outline: none;
        color: #a9b7c6;
        flex: 1;
        font-family: "Courier New", Courier, monospace;
        margin-left: 8px;
      }
      #editor-container {
        width: 100%;
        height: 100%;
      }
      .monaco-editor .margin {
        background-color: var(--java-editor) !important;
      }
      .file-item:hover {
        background-color: var(--java-hover);
      }
      .file-item.active {
        background-color: var(--java-hover);
        color: #fff;
      }
      .file-action {
        display: none;
      }
      .file-item:hover .file-action {
        display: flex;
      }
      .resizer-h {
        height: 4px;
        background: var(--java-bg);
        cursor: ns-resize;
        border-top: 1px solid var(--java-border);
        border-bottom: 1px solid var(--java-border);
        z-index: 10;
      }
      .resizer-h:hover {
        background: var(--java-accent);
      }
      .resizer-v {
        width: 4px;
        background: var(--java-bg);
        cursor: ew-resize;
        border-left: 1px solid var(--java-border);
        border-right: 1px solid var(--java-border);
        z-index: 10;
      }
      .resizer-v:hover {
        background: var(--java-accent);
      }
    </style>
  </head>
  <body class="h-screen w-screen flex flex-col overflow-hidden text-sm select-none">
    <div class="h-8 flex items-center justify-between px-3 bg-[#3c3f41] border-b border-[#242627]">
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2 font-bold text-[#a9b7c6]">
          <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="128" height="128" rx="28" fill="#212223" />
            <rect x="4" y="4" width="120" height="120" rx="24" fill="none" stroke="#3c3f41" stroke-width="4" />
            <path d="M 28 28 H 100 A 12 12 0 0 1 112 40 V 100 A 12 12 0 0 1 100 112 H 28 A 12 12 0 0 1 16 100 V 40 A 12 12 0 0 1 28 28 Z" fill="#2b2b2b" stroke="#4b6eaf" stroke-width="3" />
            <path d="M 36 50 L 26 64 L 36 78" fill="none" stroke="#cc7832" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M 48 78 V 50 L 80 78 V 50" fill="none" stroke="#a9b7c6" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M 92 50 L 102 64 L 92 78" fill="none" stroke="#4b6eaf" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" />
            <rect x="50" y="86" width="28" height="5" fill="#6a8759" rx="2" />
          </svg>
          <span>NoobIDE</span>
        </div>
        <div class="flex space-x-4 text-[13px]">
          <span class="hover:text-white cursor-pointer" onclick="openSettings()">Settings</span>
          <span class="hover:text-white cursor-pointer" onclick="saveCurrentFile()">Save</span>
          <span class="hover:text-white cursor-pointer" onclick="toggleSidebar()">Sidebar</span>
          <span class="hover:text-white cursor-pointer" onclick="openTerminalPanel()">Terminal</span>
          <span class="hover:text-white cursor-pointer text-[#4b6eaf] font-bold" onclick="runCode()">Run Program</span>
          <span class="hover:text-white cursor-pointer text-green-400 font-bold" onclick="toggleWebView()">Toggle Web View</span>
        </div>
      </div>
    </div>
    <div class="flex flex-1 overflow-hidden flex-col md:flex-row">
      <div id="sidebar" class="w-full md:w-64 bg-[#3c3f41] border-b md:border-b-0 md:border-r border-[#242627] flex flex-col flex-shrink-0 transition-all duration-300">
        <div class="h-8 flex items-center justify-between px-4 text-[11px] font-bold tracking-wider text-[#a9b7c6]">
          <span>PROJECT EXPLORER</span>
        </div>
        <div class="h-8 flex items-center justify-between px-2 bg-[#4e5254] cursor-pointer border-y border-[#242627]" onclick="toggleWorkspace()">
          <div class="flex items-center space-x-1 font-bold">
            <svg id="ws-chevron" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
            <span>WORKSPACE</span>
          </div>
          <div class="flex space-x-1">
            <svg class="w-4 h-4 hover:text-white" onclick="event.stopPropagation(); createNewFolder('')" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
            <svg class="w-4 h-4 hover:text-white" onclick="event.stopPropagation(); createNewFile('')" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
        </div>
        <div id="file-list" class="flex-1 overflow-y-auto py-1"></div>
      </div>
      <div class="flex flex-1 flex-col overflow-hidden min-w-0">
        <div class="flex flex-1 flex-col md:flex-row overflow-hidden" id="main-editor-area">
          <div class="flex flex-1 flex-col overflow-hidden min-w-[150px] min-h-[150px]" id="editor-wrapper">
            <div class="h-9 flex bg-[#3c3f41] border-b border-[#242627] overflow-x-auto scrollbar-hide flex-shrink-0" id="tabs-container"></div>
            <div class="flex-1 relative bg-[#2b2b2b]">
              <div id="editor-container" class="absolute inset-0"></div>
            </div>
          </div>
          <div class="resizer-v hidden md:block" id="v-resizer"></div>
          <div id="webview-wrapper" class="w-full md:w-1/2 flex-col hidden bg-white min-w-[150px] min-h-[150px]">
            <div class="h-9 bg-[#3c3f41] border-b border-[#242627] flex items-center px-4 justify-between flex-shrink-0">
              <span class="text-[#a9b7c6] font-bold text-xs">Real-Time Web View</span>
              <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-[#a9b7c6] cursor-pointer hover:text-white" onclick="openFileInNewTab()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                <svg class="w-4 h-4 text-[#a9b7c6] cursor-pointer hover:text-white" onclick="forceRefreshWebView()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
              </div>
            </div>
            <iframe id="webview-frame" sandbox="allow-scripts allow-modals" class="w-full flex-1 border-none bg-white"></iframe>
          </div>
        </div>
        <div class="resizer-h" id="h-resizer"></div>
        <div id="bottom-panel" class="flex flex-col bg-[#3c3f41] flex-shrink-0" style="height: 250px; min-height: 100px">
          <div class="flex h-9 border-b border-[#242627] px-2 space-x-2 items-center">
            <span class="text-[12px] font-bold px-3 py-1 cursor-pointer hover:bg-[#4e5254] transition-colors panel-tab border-b-2 border-[#4b6eaf] text-white" id="tab-problems" onclick="switchPanel('problems')">Problems</span>
            <span class="text-[12px] font-bold px-3 py-1 cursor-pointer hover:bg-[#4e5254] transition-colors panel-tab" id="tab-output" onclick="switchPanel('output')">Output</span>
            <span class="text-[12px] font-bold px-3 py-1 cursor-pointer hover:bg-[#4e5254] transition-colors panel-tab" id="tab-terminal" onclick="switchPanel('terminal')">Terminal</span>
            <div class="flex-1"></div>
            <svg class="w-4 h-4 cursor-pointer hover:text-white mr-1" onclick="clearTerminal()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            <svg class="w-4 h-4 cursor-pointer hover:text-white mr-1" id="btn-expand-term" onclick="toggleExpandTerminal()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path id="icon-expand-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4M10 10L4 4M14 14l6 6M14 10l6-6M10 14l-6 6"></path>
            </svg>
            <svg class="w-4 h-4 cursor-pointer hover:text-white mr-2" onclick="closeTerminalPanel()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </div>
          <div class="flex-1 overflow-hidden relative bg-[#2b2b2b] font-mono text-[13px] text-[#a9b7c6]">
            <div id="content-problems" class="panel-content absolute inset-0 p-4 overflow-y-auto hidden">
              <div class="text-green-500">No syntax problems detected. Code is neat!</div>
            </div>
            <div id="content-output" class="panel-content absolute inset-0 p-4 overflow-y-auto hidden">
              <div id="output-log"></div>
            </div>
            <div id="content-terminal" class="panel-content absolute inset-0 p-4 overflow-y-auto cursor-text" onclick="focusTerminal()">
              <div id="terminal-log">
                <div class="text-[#a9b7c6]">Terminal</div>
                <div class="text-[#a9b7c6]">Ready.</div>
                <br />
              </div>
              <form id="term-form" onsubmit="handleTermSubmit(event)" class="hidden terminal-input-wrapper">
                <span class="text-[#a9b7c6] font-bold">$&nbsp;</span>
                <input id="term-input" type="text" class="terminal-input" autocomplete="off" spellcheck="false" />
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div id="settings-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
      <div class="bg-[#3c3f41] border border-[#242627] p-6 w-80 text-[#a9b7c6] shadow-xl">
        <h2 class="font-bold mb-4 text-white text-lg">IDE Settings</h2>
        <label class="flex items-center space-x-2 mb-4 cursor-pointer">
          <input type="checkbox" id="setting-wrap" onchange="toggleWrap()" class="cursor-pointer" />
          <span>Enable Word Wrap</span>
        </label>
        <div class="flex justify-end mt-4">
          <button class="bg-[#4b6eaf] text-white px-4 py-1 hover:bg-[#3b5e9f]" onclick="closeSettings()">Done</button>
        </div>
      </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.36.1/min/vs/loader.min.js"></script>
    <script>
      let editor;
      let currentFile = '';
      let openTabs = [];
      let models = {};
      let isSidebarOpen = true;
      let isWorkspaceOpen = true;
      let pendingInputResolver = null;
      let autoSaveTimeout = null;
      let webViewTimeout = null;
      let fileList = [];
      let folderState = {};
      let isTerminalExpanded = false;
      let savedTerminalHeight = '250px';
      let isWrapped = false;
      const hResizer = document.getElementById('h-resizer');
      const bottomPanel = document.getElementById('bottom-panel');
      let isResizingH = false;
      const vResizer = document.getElementById('v-resizer');
      const webviewWrapper = document.getElementById('webview-wrapper');
      const iframe = document.getElementById('webview-frame');
      let isResizingV = false;
      hResizer.addEventListener('mousedown', (e) => {
        isResizingH = true;
        document.body.style.cursor = 'ns-resize';
        iframe.style.pointerEvents = 'none';
      });
      vResizer.addEventListener('mousedown', (e) => {
        isResizingV = true;
        document.body.style.cursor = 'ew-resize';
        iframe.style.pointerEvents = 'none';
      });
      document.addEventListener('mousemove', (e) => {
        if (isResizingH) {
          const offsetBottom = window.innerHeight - e.clientY;
          if (offsetBottom > 50 && offsetBottom < window.innerHeight * 0.8) {
            bottomPanel.style.height = offsetBottom + 'px';
            savedTerminalHeight = offsetBottom + 'px';
            if (editor) editor.layout();
          }
        }
        if (isResizingV) {
          const offsetRight = window.innerWidth - e.clientX;
          if (offsetRight > 150 && e.clientX > 150) {
            webviewWrapper.style.width = offsetRight + 'px';
            webviewWrapper.style.flex = 'none';
            if (editor) editor.layout();
          }
        }
      });
      document.addEventListener('mouseup', () => {
        if (isResizingH || isResizingV) {
          isResizingH = false;
          isResizingV = false;
          document.body.style.cursor = 'default';
          iframe.style.pointerEvents = 'auto';
        }
      });
      window.addEventListener('resize', () => {
        if (editor) editor.layout();
      });
      function openSettings() {
        document.getElementById('settings-modal').classList.remove('hidden');
      }
      function closeSettings() {
        document.getElementById('settings-modal').classList.add('hidden');
      }
      function toggleWrap() {
        isWrapped = document.getElementById('setting-wrap').checked;
        if (editor) editor.updateOptions({ wordWrap: isWrapped ? 'on' : 'off' });
      }
      function closeTerminalPanel() {
        bottomPanel.classList.add('hidden');
        hResizer.classList.add('hidden');
        if (editor) editor.layout();
      }
      function openTerminalPanel() {
        bottomPanel.classList.remove('hidden');
        hResizer.classList.remove('hidden');
        if (editor) editor.layout();
      }
      function toggleExpandTerminal() {
        const pathEl = document.getElementById('icon-expand-path');
        if (!isTerminalExpanded) {
          savedTerminalHeight = bottomPanel.style.height || '250px';
          bottomPanel.style.height = (window.innerHeight * 0.75) + 'px';
          isTerminalExpanded = true;
          if (pathEl) pathEl.setAttribute('d', 'M4 14h6v6M20 14h-6v6M4 10h6V4M20 10h-6V4M10 10L4 4M14 14l6 6M14 10l6-6M10 14l-6 6');
        } else {
          bottomPanel.style.height = savedTerminalHeight;
          isTerminalExpanded = false;
          if (pathEl) pathEl.setAttribute('d', 'M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4M10 10L4 4M14 14l6 6M14 10l6-6M10 14l-6 6');
        }
        if (editor) editor.layout();
      }
      async function apiCall(action, payload = {}) {
        payload.action = action;
        const res = await fetch(window.location.href, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        return res.json();
      }
      async function loadFiles() {
        const res = await apiCall('list');
        if (res.success) {
          fileList = res.files;
          renderFileList();
        }
      }
      function renderTree(nodes, parentEl, depth = 0) {
        nodes.forEach(node => {
          const item = document.createElement('div');
          item.className = 'file-item flex items-center justify-between py-1 cursor-pointer';
          item.style.paddingLeft = (depth * 12 + 8) + 'px';
          item.style.paddingRight = '8px';
          if (node.path === currentFile) item.classList.add('active');
          const left = document.createElement('div');
          left.className = 'flex items-center space-x-1 truncate';
          if (node.type === 'folder') {
            const chevron = folderState[node.path] ? 'M19 9l-7 7-7-7' : 'M9 5l7 7-7 7';
            left.innerHTML = `<svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${chevron}"></path></svg><svg class="w-4 h-4 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg><span class="truncate">${node.name}</span>`;
            left.onclick = (e) => {
              e.stopPropagation();
              toggleFolder(node.path);
            };
          } else {
            left.innerHTML = `<svg class="w-4 h-4 text-gray-400 flex-shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg><span class="truncate">${node.name}</span>`;
            left.onclick = (e) => {
              e.stopPropagation();
              openFile(node.path);
            };
          }
          const right = document.createElement('div');
          right.className = 'file-action space-x-1 hidden bg-[#4e5254] px-1 rounded';
          let actHtml = '';
          if (node.type === 'folder') {
            actHtml += `<svg class="w-3 h-3 hover:text-white" onclick="event.stopPropagation(); createNewFile('${node.path}')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>`;
          }
          actHtml += `<svg class="w-3 h-3 hover:text-white" onclick="actionNode(event, 'copy', '${node.path}')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg><svg class="w-3 h-3 hover:text-white" onclick="actionNode(event, 'rename', '${node.path}')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg><a href="?view=${encodeURIComponent(node.path)}" target="_blank" onclick="event.stopPropagation()" class="hover:text-white"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></a><a href="?download=${encodeURIComponent(node.path)}" onclick="event.stopPropagation()" class="hover:text-white"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a><svg class="w-3 h-3 text-red-400 hover:text-red-300" onclick="deleteNode(event, '${node.path}')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;
          right.innerHTML = actHtml;
          item.onmouseenter = () => right.classList.remove('hidden');
          item.onmouseleave = () => right.classList.add('hidden');
          item.appendChild(left);
          item.appendChild(right);
          parentEl.appendChild(item);
          if (node.type === 'folder' && folderState[node.path]) {
            renderTree(node.children, parentEl, depth + 1);
          }
        });
      }
      function renderFileList() {
        const listEl = document.getElementById('file-list');
        listEl.innerHTML = '';
        if (!isWorkspaceOpen) return;
        renderTree(fileList, listEl, 0);
      }
      function toggleFolder(path) {
        folderState[path] = !folderState[path];
        renderFileList();
      }
      function getExtension(filename) {
        return filename.split('.').pop().toLowerCase();
      }
      function renderTabs() {
        const tc = document.getElementById('tabs-container');
        tc.innerHTML = '';
        openTabs.forEach(tab => {
          const isActive = (tab === currentFile);
          const div = document.createElement('div');
          div.className = `flex items-center px-4 border-r border-[#242627] cursor-pointer min-w-fit space-x-2 ${isActive ? 'bg-[#2b2b2b] border-t-2 border-[#4b6eaf] text-white' : 'bg-[#3c3f41] text-[#a9b7c6] hover:bg-[#4e5254] border-t-2 border-transparent'}`;
          div.onclick = () => openFile(tab);
          div.innerHTML = `<span>${tab.split('/').pop()}</span><svg class="w-3 h-3 hover:text-red-400" onclick="closeTab(event, '${tab}')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>`;
          tc.appendChild(div);
        });
      }
      async function closeTab(e, filename) {
        e.stopPropagation();
        if (models[filename]) {
          await apiCall('write', { filename: filename, content: models[filename].getValue() });
        }
        openTabs = openTabs.filter(t => t !== filename);
        if (currentFile === filename) {
          if (openTabs.length > 0) {
            openFile(openTabs[openTabs.length - 1]);
          } else {
            currentFile = '';
            if (editor) editor.setModel(null);
            renderTabs();
            renderFileList();
          }
        } else {
          renderTabs();
        }
      }
      async function openFile(filename) {
        let ext = getExtension(filename);
        if (!models[filename]) {
          const res = await apiCall('read', { filename });
          if (res.success) {
            let lang = ext;
            if (lang === 'js') lang = 'javascript';
            if (lang === 'txt') lang = 'plaintext';
            if (!['noob', 'html', 'css', 'javascript', 'plaintext'].includes(lang)) lang = 'noob';
            models[filename] = monaco.editor.createModel(res.content, lang);
          } else {
            return;
          }
        }
        if (!openTabs.includes(filename)) openTabs.push(filename);
        currentFile = filename;
        if (editor) editor.setModel(models[filename]);
        renderTabs();
        renderFileList();
      }
      async function saveCurrentFile() {
        if (editor && currentFile && models[currentFile]) {
          await apiCall('write', { filename: currentFile, content: models[currentFile].getValue() });
          await loadFiles();
        }
      }
      async function createNewFile(parentFolder = '') {
        let name = prompt('Enter new file name (.noob, .html, .css, .js, .txt):', 'Untitled.noob');
        if (name && name.trim() !== '') {
          name = name.trim();
          const ext = getExtension(name);
          if (!['noob', 'html', 'css', 'js', 'txt'].includes(ext)) {
            name = name.replace(/\.[^/.]+$/, '') + '.noob';
          }
          const fullPath = parentFolder ? parentFolder + '/' + name : name;
          await apiCall('write', { filename: fullPath, content: '' });
          await loadFiles();
          if (parentFolder) folderState[parentFolder] = true;
          openFile(fullPath);
        }
      }
      async function createNewFolder(parentFolder = '') {
        let name = prompt('Enter new folder name:');
        if (name && name.trim() !== '') {
          const fullPath = parentFolder ? parentFolder + '/' + name.trim() : name.trim();
          await apiCall('create_folder', { path: fullPath });
          await loadFiles();
          if (parentFolder) folderState[parentFolder] = true;
        }
      }
      async function actionNode(e, action, path) {
        e.stopPropagation();
        if (action === 'rename') {
          let newName = prompt('Rename to:', path);
          if (newName && newName !== path) {
            await apiCall('rename', { old: path, new: newName });
            if (models[path]) {
              models[newName] = models[path];
              delete models[path];
              openTabs = openTabs.map(t => t === path ? newName : t);
              if (currentFile === path) currentFile = newName;
            }
            await loadFiles();
            renderTabs();
          }
        } else if (action === 'copy') {
          let newName = prompt('Copy to:', 'copy_of_' + path);
          if (newName && newName !== path) {
            await apiCall('copy', { old: path, new: newName });
            await loadFiles();
          }
        }
      }
      async function deleteNode(e, path) {
        e.stopPropagation();
        if (confirm(`Are you sure you want to delete ${path}?`)) {
          await apiCall('delete', { path });
          if (models[path]) {
            models[path].dispose();
            delete models[path];
          }
          openTabs = openTabs.filter(t => t !== path && !t.startsWith(path + '/'));
          for (let p in models) {
            if (p.startsWith(path + '/')) {
              models[p].dispose();
              delete models[p];
            }
          }
          if (currentFile === path || currentFile.startsWith(path + '/')) {
            currentFile = openTabs.length > 0 ? openTabs[openTabs.length - 1] : '';
            if (currentFile) openFile(currentFile);
            else if (editor) editor.setModel(null);
          }
          await loadFiles();
          renderTabs();
          forceRefreshWebView();
        }
      }
      function toggleSidebar() {
        const sb = document.getElementById('sidebar');
        isSidebarOpen = !isSidebarOpen;
        sb.style.display = isSidebarOpen ? 'flex' : 'none';
        if (editor) setTimeout(() => editor.layout(), 100);
      }
      function toggleWorkspace() {
        isWorkspaceOpen = !isWorkspaceOpen;
        const ch = document.getElementById('ws-chevron');
        ch.style.transform = isWorkspaceOpen ? 'rotate(0deg)' : 'rotate(-90deg)';
        renderFileList();
      }
      function toggleWebView() {
        if (webviewWrapper.classList.contains('hidden')) {
          webviewWrapper.classList.remove('hidden');
          webviewWrapper.classList.add('flex');
          vResizer.classList.remove('hidden');
          refreshWebView();
        } else {
          webviewWrapper.classList.add('hidden');
          webviewWrapper.classList.remove('flex');
          vResizer.classList.add('hidden');
        }
        if (editor) setTimeout(() => editor.layout(), 100);
      }
      async function forceRefreshWebView() {
        await saveCurrentFile();
        refreshWebView();
      }
      async function processNoobTags(text) {
        if (!text) return text;
        const regex = /<noob>([\s\S]*?)<\/noob>/g;
        let matches = [...text.matchAll(regex)];
        let result = text;
        for (let m of matches) {
          let code = m[1];
          let res = await apiCall('execute', {code});
          if (res.success) {
            let compiler = new JITCompiler();
            let js = compiler.compile(res.ast);
            let output = [];
            let localBuiltins = { ...builtins, yo: async (...args) => {
              let str = args.map(val => val !== undefined ? (typeof val === 'object' ? JSON.stringify(val) : String(val)) : '').join(' ');
              output.push(str);
            }};
            try {
              let exec = new AsyncFunction('builtins', js);
              await exec(localBuiltins);
              result = result.replace(m[0], output.join(' '));
            } catch (e) {
              console.error(e);
            }
          }
        }
        return result;
      }
      async function generateCombinedHTML() {
        const res = await apiCall('read_web');
        if (!res.success) return '';
        let html = res.html || '';
        let css = res.css || '';
        let js = res.js || '';
        if (models['index.html']) html = models['index.html'].getValue();
        if (models['style.css']) css = models['style.css'].getValue();
        if (models['script.js']) js = models['script.js'].getValue();
        html = await processNoobTags(html);
        css = await processNoobTags(css);
        js = await processNoobTags(js);
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        if (css.trim() !== '') {
          const style = doc.createElement('style');
          style.textContent = css;
          doc.head.appendChild(style);
        }
        if (js.trim() !== '') {
          const script = doc.createElement('script');
          script.textContent = js;
          doc.body.appendChild(script);
        }
        return "<!DOCTYPE html>\n" + doc.documentElement.outerHTML;
      }
      async function refreshWebView() {
        if (webviewWrapper.classList.contains('hidden')) return;
        const finalHTML = await generateCombinedHTML();
        if (finalHTML) iframe.srcdoc = finalHTML;
      }
      function openFileInNewTab() {
        if (currentFile) {
          window.open('?view=' + encodeURIComponent(currentFile), '_blank');
        }
      }
      function switchPanel(panelId) {
        document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('border-b-2', 'border-[#4b6eaf]', 'text-white'));
        document.querySelectorAll('.panel-content').forEach(p => p.classList.add('hidden'));
        document.getElementById('tab-' + panelId).classList.add('border-b-2', 'border-[#4b6eaf]', 'text-white');
        document.getElementById('content-' + panelId).classList.remove('hidden');
        if (panelId === 'terminal') focusTerminal();
      }
      function appendOutput(text) {
        const log = document.getElementById('output-log');
        const div = document.createElement('div');
        div.className = 'terminal-line';
        div.textContent = text;
        log.appendChild(div);
        const panel = document.getElementById('content-output');
        panel.scrollTop = panel.scrollHeight;
      }
      function appendTerminal(text, colorClass = "text-[#a9b7c6]") {
        const log = document.getElementById('terminal-log');
        const div = document.createElement('div');
        div.className = 'terminal-line ' + colorClass;
        div.textContent = text;
        log.appendChild(div);
        const panel = document.getElementById('content-terminal');
        panel.scrollTop = panel.scrollHeight;
      }
      function clearTerminal() {
        document.getElementById('terminal-log').innerHTML = '';
        document.getElementById('output-log').innerHTML = '';
        document.getElementById('term-form').classList.add('hidden');
      }
      function focusTerminal() {
        const input = document.getElementById('term-input');
        if (!document.getElementById('term-form').classList.contains('hidden')) {
          input.focus();
        }
      }
      function requestInput(promptText) {
        if (promptText) appendTerminal(promptText);
        openTerminalPanel();
        switchPanel('terminal');
        const form = document.getElementById('term-form');
        const input = document.getElementById('term-input');
        form.classList.remove('hidden');
        input.value = '';
        input.focus();
        return new Promise(resolve => {
          pendingInputResolver = resolve;
        });
      }
      function handleTermSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('term-input');
        const val = input.value;
        appendTerminal("$ " + val);
        document.getElementById('term-form').classList.add('hidden');
        if (pendingInputResolver) {
          const res = pendingInputResolver;
          pendingInputResolver = null;
          res(val);
        }
      }
      class JITCompiler {
        constructor() {
          this.inConstructor = false;
        }
        compile(node, isLHS = false) {
          if (!node) return "null";
          if (node.blockStatements !== undefined) {
            return node.blockStatements.map(s => this.compile(s) + ";\n").join("");
          }
          if (node.varDeclName !== undefined) {
            return `window['${node.varDeclName}'] = ${this.compile(node.varDeclExpr)}`;
          }
          if (node.assignTarget !== undefined) {
            return `${this.compile(node.assignTarget, true)} ${node.assignOp} ${this.compile(node.assignExpr)}`;
          }
          if (node.callFuncExpr !== undefined) {
            let fn = this.compile(node.callFuncExpr, true);
            let args = node.callArgs.map(a => this.compile(a)).join(', ');
            let callStr = "";
            if (["yo", "ask", "rng", "len", "rWb", "fileWrite", "fileRead"].includes(fn)) {
              callStr = `builtins.${fn}(${args})`;
            } else {
              if (args === "") {
                callStr = `(typeof ${fn} === 'function' ? ${fn}() : undefined)`;
              } else {
                callStr = `(typeof ${fn} === 'function' ? ${fn}(${args}) : ${fn}[${args}])`;
              }
            }
            if (this.inConstructor) return callStr;
            return `(await ${callStr})`;
          }
          if (node.funcParams !== undefined) {
            let params = node.funcParams.join(', ');
            let body = this.compile(node.funcBody);
            return node.funcName ? `async function ${node.funcName}(${params}) { ${body} }` : `async function(${params}) { ${body} }`;
          }
          if (node.isReturn) {
            if (node.returnValueExpr === null) return `return`;
            return `return ${this.compile(node.returnValueExpr)}`;
          }
          if (node.ifCond !== undefined) {
            let cond = this.compile(node.ifCond);
            let thenB = this.compile(node.thenBlock);
            let elseB = node.elseBlock ? `else { ${this.compile(node.elseBlock)} }` : "";
            return `if (${cond}) { ${thenB} } ${elseB}`;
          }
          if (node.repeatCount !== undefined) {
            let count = this.compile(node.repeatCount);
            let i = "__i" + Math.floor(Math.random() * 10000);
            return `for(let ${i}=0; ${i}<(${count}); ${i}++) { ${this.compile(node.repeatBody)} }`;
          }
          if (node.whileCond !== undefined) {
            return `while (${this.compile(node.whileCond)}) { ${this.compile(node.whileBody)} }`;
          }
          if (node.binLeft !== undefined) {
            let op = node.binOp;
            if (op === '==') op = '===';
            if (op === '!=') op = '!==';
            if (op === 'and') op = '&&';
            if (op === 'orr') op = '||';
            if (op === 'greater than') op = '>';
            if (op === 'less than') op = '<';
            if (op === '^') return `Math.pow(${this.compile(node.binLeft)}, ${this.compile(node.binRight)})`;
            return `(${this.compile(node.binLeft)} ${op} ${this.compile(node.binRight)})`;
          }
          if (node.unOp !== undefined) {
            let op = node.unOp;
            if (op === 'not') op = '!';
            return `(${op}${this.compile(node.unOperand)})`;
          }
          if (node.arrElements !== undefined) {
            return `[` + node.arrElements.map(e => this.compile(e)).join(', ') + `]`;
          }
          if (node.mapEntries !== undefined) {
            let pairs = [];
            for (let k in node.mapEntries) pairs.push(`"${k}": ${this.compile(node.mapEntries[k])}`);
            return `{` + pairs.join(', ') + `}`;
          }
          if (node.idxTarget !== undefined) {
            return `${this.compile(node.idxTarget, true)}[${this.compile(node.idxIndex)}]`;
          }
          if (node.memTarget !== undefined) {
            let tgt = this.compile(node.memTarget, true);
            if (!isLHS) return `(typeof ${tgt}.${node.memMember} === 'function' ? ${tgt}.${node.memMember}() : ${tgt}.${node.memMember})`;
            return `${tgt}.${node.memMember}`;
          }
          if (node.isLiteral) {
            if (typeof node.litValue === 'string') return JSON.stringify(node.litValue);
            return String(node.litValue);
          }
          if (node.varRefName !== undefined) {
            if (!isLHS) return `(typeof ${node.varRefName} === 'function' ? ${node.varRefName}() : ${node.varRefName})`;
            return node.varRefName;
          }
          if (node.className !== undefined) {
            return `class ${node.className} {\n  ` + node.classMethods.map(m => this.compile(m)).join('\n  ') + `\n}`;
          }
          if (node.methodName !== undefined) {
            let params = node.methodParams.join(', ');
            let prevInConstructor = this.inConstructor;
            let isInit = (node.methodName === 'init');
            this.inConstructor = isInit;
            let body = this.compile(node.methodBody);
            this.inConstructor = prevInConstructor;
            if (isInit) return `constructor(${params}) { ${body} }`;
            return `async ${node.methodName}(${params}) { ${body} }`;
          }
          if (node.newClassName !== undefined) {
            return `new ${node.newClassName}(${node.newArgs.map(a => this.compile(a)).join(', ')})`;
          }
          if (node.isThis) {
            return `this`;
          }
          return "";
        }
      }
      const builtins = {
        yo: async (...args) => {
          let str = args.map(val => val !== undefined ? (typeof val === 'object' ? JSON.stringify(val) : String(val)) : '').join(' ');
          appendTerminal(str);
          appendOutput(str);
        },
        rng: async (min = 0, max = 100) => Math.floor(Math.random() * (max - min + 1)) + min,
        len: async (data) => {
          if (typeof data === 'string' || Array.isArray(data)) return data.length;
          if (typeof data === 'object' && data) return Object.keys(data).length;
          return 0;
        },
        ask: async (promptText) => {
          return await requestInput(promptText || '');
        },
        rWb: async (html = '', css = '', js = '') => {
          html = await processNoobTags(html);
          css = await processNoobTags(css);
          js = await processNoobTags(js);
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          if (css.trim() !== '') {
            const style = doc.createElement('style');
            style.textContent = css;
            doc.head.appendChild(style);
          }
          if (js.trim() !== '') {
            const script = doc.createElement('script');
            script.textContent = js;
            doc.body.appendChild(script);
          }
          const finalHTML = "<!DOCTYPE html>\n" + doc.documentElement.outerHTML;
          const wv = document.getElementById('webview-wrapper');
          wv.classList.remove('hidden');
          wv.classList.add('flex');
          document.getElementById('v-resizer').classList.remove('hidden');
          document.getElementById('webview-frame').srcdoc = finalHTML;
          if (editor) editor.layout();
          appendTerminal("[rWb] Rendered virtually directly to memory view", "text-green-400");
          return null;
        },
        fileWrite: async (name, content) => {
          await apiCall('write', { filename: name, content: content });
          await loadFiles();
          return true;
        },
        fileRead: async (name) => {
          let res = await apiCall('read', { filename: name });
          return res.success ? res.content : null;
        }
      };
      const AsyncFunction = Object.getPrototypeOf(async function(){}).constructor;
      async function runCode() {
        await saveCurrentFile();
        openTerminalPanel();
        switchPanel('terminal');
        clearTerminal();
        appendTerminal("[Running " + currentFile + "]");
        appendOutput("[Executing " + currentFile + "]");
        if (!currentFile.endsWith('.noob')) {
          appendTerminal("[Skipped] Not a .noob script.");
          return;
        }
        if (!editor || !models[currentFile]) return;
        const code = models[currentFile].getValue();
        try {
          const res = await apiCall('execute', { code: code });
          if (res.success) {
            const compiler = new JITCompiler();
            const compiledJS = compiler.compile(res.ast);
            const exec = new AsyncFunction('builtins', compiledJS);
            const start = performance.now();
            await exec(builtins);
            const time = Math.round(performance.now() - start);
            appendTerminal("[Finished in " + time + "ms]");
          } else {
            appendTerminal("PARSER ERROR:\n" + res.error, "text-red-400");
            switchPanel('problems');
            document.getElementById('content-problems').innerHTML = "<div class='text-red-400'>PARSER ERROR:<br>" + res.error + "</div>";
          }
        } catch (err) {
          appendTerminal("RUNTIME ERROR:\n" + err.message, "text-red-400");
        }
      }
      require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.36.1/min/vs' } });
      require(['vs/editor/editor.main'], async function() {
        monaco.languages.register({ id: 'noob' });
        monaco.languages.setMonarchTokensProvider('noob', {
          tokenizer: {
            root: [
              [/\b(?:var|fun|ret|cls|new|ths|tru|fls|nul|whl|rpt|and|orr|not|if|els|end)\b/, "keyword"],
              [/\b(?:yo|ask|rng|len|rWb)\b/, "type.identifier"],
              [/\b(?:greater than|less than)\b/, "keyword"],
              [/[a-zA-Z_]\w*/, "identifier"],
              [/[0-9]+(\.[0-9]+)?/, "number"],
              [/~.*/, "string"],
              [/'/, "string", "@string_single"]
            ],
            string_single: [
              [/[^\\']+/, "string"],
              [/\\./, "string.escape"],
              [/'/, "string", "@pop"]
            ]
          }
        });
        monaco.editor.defineTheme('java-dark', {
          base: 'vs-dark',
          inherit: true,
          rules: [{
            token: 'keyword',
            foreground: 'cc7832',
            fontStyle: 'bold'
          }, {
            token: 'type.identifier',
            foreground: '9876aa'
          }, {
            token: 'identifier',
            foreground: 'a9b7c6'
          }, {
            token: 'string',
            foreground: '6a8759'
          }, {
            token: 'number',
            foreground: '6897bb'
          }],
          colors: {
            'editor.background': '#2b2b2b',
            'editor.lineHighlightBackground': '#323232'
          }
        });
        editor = monaco.editor.create(document.getElementById('editor-container'), {
          theme: 'java-dark',
          automaticLayout: true,
          minimap: { enabled: false },
          fontSize: 14,
          fontFamily: "'Courier New', Consolas, monospace",
          scrollBeyondLastLine: false,
          wordWrap: isWrapped ? 'on' : 'off',
          padding: { top: 16 }
        });
        editor.onDidChangeModelContent(() => {
          clearTimeout(autoSaveTimeout);
          clearTimeout(webViewTimeout);
          autoSaveTimeout = setTimeout(() => {
            saveCurrentFile();
          }, 1000);
          const ext = getExtension(currentFile);
          if (['html', 'css', 'js'].includes(ext)) {
            webViewTimeout = setTimeout(() => {
              refreshWebView();
            }, 400);
          }
        });
        await loadFiles();
        if (fileList.length > 0) openFile('main.noob');
        else await createNewFile();
      });
      window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
          e.preventDefault();
          saveCurrentFile();
          appendOutput("[Saved " + currentFile + "]");
          forceRefreshWebView();
        }
      });
    </script>
  </body>
</html>