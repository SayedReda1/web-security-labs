from flask import Flask, render_template
from threading import Thread

app1 = Flask(__name__)
app2 = Flask(__name__)

@app1.route('/')
def victim():
    return render_template('victim.html')

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