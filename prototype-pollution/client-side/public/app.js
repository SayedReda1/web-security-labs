/*
 * A small custom parser turns nested query parameters into JavaScript objects.
 *
 * Intentionally vulnerable:
 */

function setDeep(target, keys, value) {
  let current = target;

  // FIXME: does not reject dangerous property names such as __proto__.
  for (let i = 0; i < keys.length - 1; i++) {
    const key = keys[i];

    if (!current[key]) {
      current[key] = {};
    }

    current = current[key];
  }

  current[keys[keys.length - 1]] = value;
}

function parseSearchParams(search) {
  const params = {};

  for (const part of search.replace(/^\?/, "").split("&")) {
    if (!part) continue;

    const [rawKey, rawValue = ""] = part.split("=");
    const key = decodeURIComponent(rawKey);
    const value = decodeURIComponent(rawValue.replace(/\+/g, " "));

    // Supports: q=phone, filter[sort]=price, etc.
    const keys = key.match(/[^[\]]+/g) || [];
    if (keys.length) {
      setDeep(params, keys, value);
    }
  }

  return params;
}

function renderSearchState(config) {
  const results = document.querySelector("#results");

  if (config.html) {
    results.innerHTML = config.html;
    return;
  }

  const term = config.term || "(all products)";
  results.textContent = `Results for: ${term}`;
}

function renderPreview(config) {
  if (!config.preview) return;

  const frame = document.createElement("iframe");
  frame.srcdoc = config.preview; // DOM XSS sink
  frame.style.width = "100%";
  frame.style.height = "120px";
  frame.style.border = "1px solid #ddd";
  document.querySelector("#results").appendChild(frame);
}

function renderProducts(products) {
  const list = document.createElement("ul");

  for (const product of products) {
    const item = document.createElement("li");
    item.textContent = `${product.name} - $${product.price} (${product.category})`;
    list.appendChild(item);
  }

  return list;
}

async function fetchProducts(term) {
  // FIXME: pollutable object used in fetch API
  const options = {};

  try {
    const response = await fetch(
      `/api/products?q=${encodeURIComponent(term)}`,
      options
    );
    if (!response.ok) return null;
    return await response.json();
  } catch {
    return null;
  }
}

function render() {
  const params = parseSearchParams(location.search);

  // FIXME: Object missing properties which can be polluted
  const config = {
    term: params.q || ""
  };

  // FIXME: Flawed protection for preview property
  Object.defineProperty(config, preview, { writable: false });

  renderSearchState(config);
  renderPreview(config);

  document.querySelector("#q").value = config.term;

  fetchProducts(config.term).then((products) => {
    if (products) {
      document.querySelector("#results").appendChild(renderProducts(products));
    }
  });
}

document.querySelector("#searchForm").addEventListener("submit", (e) => {
  e.preventDefault();

  const q = encodeURIComponent(document.querySelector("#q").value);
  location.search = q ? `?q=${q}` : "";
});

render();
