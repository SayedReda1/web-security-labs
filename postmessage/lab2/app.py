from flask import Flask, render_template, session, redirect, request, jsonify, url_for
from threading import Thread
import secrets

app1 = Flask(__name__)
app2 = Flask(__name__)

app1.secret_key = secrets.token_hex(32)

ACCESS_TOKEN = secrets.token_hex(32)

@app1.route('/')
def victim():
    return render_template('victim-login.html')

@app1.route('/login', methods=['GET'])
def login():
    if session.get('username'):
        return redirect(url_for('oauth'))
    return render_template('victim-login.html')

@app1.route('/api/login', methods=['POST'])
def authenticate():
    if session.get('username'):
        return redirect(url_for('oauth'))

    username = request.form['username']
    password = request.form['password']

    if not username or not password:
        return "Missing username or password", 400

    if username == 'sayed' and password == 'password123':
        session['username'] = 'sayed'
        return redirect(url_for('oauth'))
    else:
        return "Invalid username or password", 400

@app1.route('/oauth', methods=['GET'])
def oauth():
    if not session.get('username'):
        return redirect(url_for('login'))

    return render_template('victim-oauth.html', token=ACCESS_TOKEN)


@app2.route('/')
def attacker():
    return render_template('attacker.html')


def run_app1():
    app1.run(host='0.0.0.0', port=5000)

def run_app2():
    app2.run(host='0.0.0.0', port=5001)


if __name__ == '__main__':
    Thread(target=run_app1).start()
    Thread(target=run_app2).start()