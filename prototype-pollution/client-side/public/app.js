/*
 * Scenario:
 * The store keeps search state in the URL so users can share filtered searches.
 * A small custom parser turns nested query parameters into JavaScript objects.
 *
 * Intentionally vulnerable:
 * setDeep() does not reject dangerous property names such as __proto__.
 */

function setDeep(target, keys, value) {
  let current = target;

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

/*
 * Intentionally vulnerable:
 * The request options object starts empty and user-supplied settings are
 * merged into it without rejecting dangerous keys. Any option not set as an
 * own property (method, headers, credentials, ...) is resolved through the
 * prototype chain when fetch() reads it.
 *
 * Direct injection:      /?request[method]=DELETE
 * Prototype pollution:   /?__proto__[headers][X-Debug]=pwned
 */
async function fetchProducts(term) {
  const params = parseSearchParams(location.search);
  const settings = params.request || {};
  const options = {};

  for (const key of Object.keys(settings)) {
    setDeep(options, [key], settings[key]);
  }

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

  // Normal application object. It has no own `html` or `preview` property.
  const config = {
    term: params.q || ""
  };

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
