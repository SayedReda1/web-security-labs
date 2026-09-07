from flask import Flask, request, render_template

app = Flask(__name__)

@app.route("/profile")
@app.route("/profile/<path:path>")
def account(path=""):
    user = request.cookies.get("user", "guest")

    if user == "admin":
        apikey = "FLAG{c4ch3_d3c3p7i0n_s0_34sy!!!}"
    else:
        apikey = "pHqVkWWMWQrdGEoBwQS65vULXOkmjY5f"

    return render_template("profile.html", username=user, apikey=apikey)

@app.route("/")
def index():
    return render_template("index.html")

app.run(host="0.0.0.0", port=5000)