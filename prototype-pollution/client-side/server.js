const express = require("express");
const path = require("path");

const products = [
  { id: 1, name: "Wireless Phone", category: "electronics", price: 299 },
  { id: 2, name: "Laptop Pro", category: "electronics", price: 1299 },
  { id: 3, name: "Bluetooth Speaker", category: "electronics", price: 79 },
  { id: 4, name: "Coffee Maker", category: "appliances", price: 89 },
  { id: 5, name: "Desk Lamp", category: "home", price: 35 }
];

const searches = [];

const app = express();
app.use(express.json());
app.use(express.static(path.join(__dirname, "public")));

app.get("/api/products", (req, res) => {
  const q = typeof req.query.q === "string" ? req.query.q.trim() : "";

  if (q) {
    searches.push({
      term: q,
      method: req.method,
      headers: req.headers,
      at: new Date().toISOString()
    });
  }

  const term = q.toLowerCase();
  const results = term
    ? products.filter((p) => p.name.toLowerCase().includes(term))
    : products;

  res.json(results);
});

app.get("/api/searches", (req, res) => {
  res.json(searches);
});

app.listen(3000, () => {
  console.log("Server running at http://localhost:3000");
});
