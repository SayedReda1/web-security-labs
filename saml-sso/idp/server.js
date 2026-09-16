const express = require('express');
const bodyParser = require('body-parser');
const session = require('express-session');
const saml = require('samlify');
const validator = require('@authenio/samlify-node-xmllint');
const flash = require('connect-flash')
const fs = require('fs')

const users = JSON.parse(fs.readFileSync('users.json'))

// samlify needs an XML schema validator to check requests/responses are well-formed.
saml.setSchemaValidator(validator)

const PORT = 5000
const SP = 'http://localhost:3000'
const app = express();

app.set('view engine', 'ejs');
app.set('views', './views');

app.use(bodyParser.urlencoded({ extended: true }))
app.use(bodyParser.json())
app.use(flash)
app.use(session({
  secret: 'idp-demo-secret-change-me',
  resave: false,
  saveUninitialized: true
}))

// --- build Idp Identity
const idp = saml.IdentityProvider({
  entityID: 'http://localhost:5000/metadata',
  singleSignOnService: [{
    Binding: saml.Constants.namespace.binding.redirect,
    Location: 'http://localhost:5000/sso'
  }],
  signingCert: fs.readFileSync('./cert/idp-public-cert.pem'),
  privateKey: fs.readFileSync('./cert/idp-private-key.pem')
});

// --- load sp we trust
async function getServiceProvider() {
  const response = await fetch(`${SP}/metadata`);
  if (!response.ok) {
    throw new Error(
      `Failed to fetch IdP metadata: ${response.status} ${response.statusText}`
    );
  }

  const metadata = await response.text();
  return saml.ServiceProvider({
    metadata
  });
}

// --- web endpoints ---
app.get('/metadata', (req, res) => {
  res.header('Content-Type', 'text/xml').send(idp.getMetadata())
})

app.get('/sso', async (req, res) => {
  try {
    // parse the incoming AuthnRequest so the response can link InResponseTo,
    // and keep RelayState so it can be echoed back to the SP
    const sp = await getServiceProvider();
    req.session.authnRequest = await idp.parseLoginRequest(sp, 'redirect', req);
    req.session.relayState = req.query.RelayState;

    if (req.session.user) {
      // already logged in
      return issueResponse(req, res, req.session.user);
    }

    res.redirect('/login');
  } catch (err) {
    console.error('Failed to parse SAML request:', err);
    res.status(400).send('Failed to parse SAML request: ' + err.message);
  }
})

app.post('/login', (req, res) => {
  const { username, password } = req.body;
  const user = users.find(u => u.username === username && u.password === password);

  if (!user) {
    req.flash('error', 'Invalid username or password')
    return res.redirect('/login');
  }

  req.session.user = user;
  return issueResponse(req, res, user);
})

app.get('/login', (req, res) => {
  return res.render('login.ejs', { error: req.flash('error')[0] })
})

app.get('/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/login'));
});


// --- issueing the assertion response ---
async function issueResponse(req, res, user) {
  try {
    if (!req.session.authnRequest) {
      // never take credentials without an AuthnRequest: the response would
      // have nothing to correlate with (no InResponseTo)
      return res.status(400).send(
        'No pending SAML authentication request — start again from the service provider.'
      );
    }

    const sp = await getServiceProvider()
    const { context } = await idp.createLoginResponse(
      sp,
      req.session.authnRequest,
      'redirect',
      {
        nameID: user.email,
        attributes: {
          email: user.email,
          displayName: user.displayName,
          role: user.role
        }
      },
      { relayState: req.session.relayState }
    );

    // deliver the SAMLResponse to the SP's ACS via an HTTP-redirect.
    // Redirect is used instead of the usual POST binding purely to keep the
    // whole flow browser-navigable (walk through the query string with Burp);
    // the SP accepts both bindings.
    res.redirect(context);

  } catch (err) {
    console.error('Failed to build SAML response:', err);
    res.status(500).send('Failed to build SAML response: ' + err.message);
  }
}

// --- start the server
app.listen(PORT, () => {
  console.log(`Identity provider running on port ${PORT}`)
})