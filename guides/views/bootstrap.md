# 🧾 Bootstrap 5 Cheat Sheet

## 📦 Container e Grid

```html
<div class="container">...</div>        <!-- Fixo -->
<div class="container-fluid">...</div>  <!-- 100% width -->

<!-- Grid: 12 colunas -->
<div class="row">
  <div class="col-6 col-md-4">...</div>
  <div class="col-6 col-md-8">...</div>
</div>
```

## 🎨 Cores e Backgrounds

```html
<!-- Texto -->
<p class="text-primary">Texto Azul</p>
<p class="text-danger">Erro</p>

<!-- Fundo -->
<div class="bg-success text-white">Sucesso</div>

<!-- Tons claros -->
<div class="bg-light text-dark">Claro</div>
```

## 📏 Espaçamento (Padding & Margin)

```html
<!-- m = margin / p = padding -->
<div class="m-3">margem 1rem</div>
<div class="p-2">padding 0.5rem</div>

<!-- Direções -->
.mt-3, .mb-2, .ms-1, .me-0 <!-- top, bottom, start (esq), end (dir) -->

<!-- Auto -->
.mx-auto <!-- centraliza horizontalmente -->
```

## 🔤 Tipografia

```html
<h1 class="display-1">Título</h1>
<p class="lead">Texto de destaque</p>
<small class="text-muted">Texto menor</small>
<mark>Marcado</mark>
```

## 🎛️ Botões

```html
<button class="btn btn-primary">Principal</button>
<button class="btn btn-outline-secondary">Contorno</button>
<button class="btn btn-success btn-sm">Pequeno</button>
```

## 📋 Tabelas

```html
<table class="table">
  <thead class="table-light">
    <tr><th>#</th><th>Nome</th></tr>
  </thead>
  <tbody>
    <tr><td>1</td><td>Ana</td></tr>
  </tbody>
</table>

<!-- Variantes -->
.table-striped, .table-bordered, .table-hover, .table-dark
```

## 🧩 Cards

```html
<div class="card" style="width: 18rem;">
  <img src="..." class="card-img-top" alt="...">
  <div class="card-body">
    <h5 class="card-title">Título</h5>
    <p class="card-text">Texto de exemplo</p>
    <a href="#" class="btn btn-primary">Ação</a>
  </div>
</div>
```

## 📦 Componentes comuns

### Navbar

```html
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="#">Logo</a>
</nav>
```

### Alertas

```html
<div class="alert alert-warning" role="alert">
  Aviso!
</div>
```

### Formulários

```html
<form>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control">
  </div>
  <button type="submit" class="btn btn-primary">Enviar</button>
</form>
```

## ⬇️ Collapse e Accordion

```html
<a class="btn btn-primary" data-bs-toggle="collapse" href="#demo">Mostrar</a>
<div class="collapse" id="demo">
  <div class="card card-body">Texto oculto</div>
</div>
```

## 📌 Modal

```html
<!-- Botão -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#meuModal">Abrir</button>

<!-- Modal -->
<div class="modal fade" id="meuModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Título</h5></div>
      <div class="modal-body">Corpo do modal</div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>
```

## 🔁 Spinner

```html
<div class="spinner-border text-primary" role="status"></div>
```

## ✅ Checkboxes e Radios

```html
<div class="form-check">
  <input class="form-check-input" type="checkbox" value="" id="check1">
  <label class="form-check-label" for="check1">Lembrar-me</label>
</div>

<div class="form-check">
  <input class="form-check-input" type="radio" name="example" id="radio1">
  <label class="form-check-label" for="radio1">Opção 1</label>
</div>
```

---

# 🧾 Bootstrap 5 – Utilitários (Classes Úteis)

## 📏 Espaçamento (`m-*` e `p-*`)

| Classe    | Descrição                            |
| --------- | ------------------------------------ |
| `m-3`     | Margem de `1rem` (em todos os lados) |
| `mt-2`    | Margem superior                      |
| `mb-1`    | Margem inferior                      |
| `ms-0`    | Margem esquerda (start)              |
| `me-auto` | Margem direita automática            |
| `p-2`     | Padding em todos os lados            |
| `px-3`    | Padding horizontal                   |
| `py-1`    | Padding vertical                     |

💡 *Valores: 0–5, `auto`*

---

## 🔠 Texto

| Classe           | Resultado               |
| ---------------- | ----------------------- |
| `text-start`     | Alinhado à esquerda     |
| `text-center`    | Centralizado            |
| `text-end`       | Alinhado à direita      |
| `text-uppercase` | MAIÚSCULAS              |
| `text-lowercase` | minúsculas              |
| `fw-bold`        | Negrito (`font-weight`) |
| `fst-italic`     | Itálico (`font-style`)  |
| `text-muted`     | Texto acinzentado       |
| `text-primary`   | Cor azul Bootstrap      |

---

## 🎨 Background e Bordas

| Classe               | Descrição                     |
| -------------------- | ----------------------------- |
| `bg-primary`         | Fundo azul                    |
| `bg-light`           | Fundo cinza claro             |
| `bg-dark text-white` | Fundo escuro com texto branco |
| `border`             | Borda simples                 |
| `border-0`           | Remove borda                  |
| `border-top`         | Borda apenas no topo          |
| `rounded`            | Cantos arredondados           |
| `rounded-circle`     | Formato circular              |
| `shadow`             | Sombra padrão                 |
| `shadow-lg`          | Sombra maior                  |

---

## 📐 Tamanho

| Classe       | Descrição                      |
| ------------ | ------------------------------ |
| `w-100`      | Largura 100%                   |
| `w-50`       | Largura 50%                    |
| `h-100`      | Altura 100%                    |
| `min-vh-100` | Altura mínima 100% da viewport |
| `mw-100`     | Largura máxima 100%            |

---

## 📱 Display e Flex

| Classe                   | Função                              |
| ------------------------ | ----------------------------------- |
| `d-none`                 | Esconde o elemento                  |
| `d-block`                | Display block                       |
| `d-inline`               | Display inline                      |
| `d-flex`                 | Ativa flexbox                       |
| `justify-content-center` | Centraliza horizontalmente          |
| `align-items-center`     | Centraliza verticalmente            |
| `flex-column`            | Direção em coluna                   |
| `flex-wrap`              | Quebra de linha ativada             |
| `gap-2`                  | Espaçamento entre itens (flex/grid) |

---

## 🔁 Posição

| Classe              | Efeito                                          |
| ------------------- | ----------------------------------------------- |
| `position-relative` | Necessária para posicionamento absoluto interno |
| `position-absolute` | Posicionamento absoluto                         |
| `top-0`, `bottom-0` | Posiciona no topo/baixo                         |
| `start-0`, `end-0`  | Posiciona esquerda/direita                      |
| `translate-middle`  | Centraliza com `top:50%; left:50%` + transform  |

---

## ⬇️ Overflow, Visibilidade e Outros

| Classe            | Descrição                           |
| ----------------- | ----------------------------------- |
| `overflow-auto`   | Scroll automático quando necessário |
| `overflow-hidden` | Oculta overflow                     |
| `invisible`       | Invisível, mas ocupa espaço         |
| `visible`         | Visível                             |
| `z-1`, `z-3`      | Z-index                             |

---

## ⏹️ Tamanhos de elementos

```html
<img src="..." class="img-fluid">      <!-- Responsivo -->
<img src="..." class="w-100">          <!-- Largura total -->
<div class="ratio ratio-16x9">         <!-- Aspect ratio -->
  <iframe ...></iframe>
</div>
```

---

## 🧪 Exemplo prático

```html
<div class="d-flex justify-content-between align-items-center bg-light p-3 border rounded shadow">
  <div class="fw-bold">Logo</div>
  <button class="btn btn-sm btn-outline-primary">Login</button>
</div>
```
