const express = require('express');
const bodyParser = require('body-parser');
const session = require('express-session');
const saml = require('samlify');
const validator = require('@authenio/samlify-node-xmllint');

saml.setSchemaValidator(validator);

const PORT = 3000;
const IDP = 'http://localhost:5000'
const app = express();

app.set('view engine', 'ejs');
app.set('views', './views');

app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(session({
  secret: 'sp-demo-secret-change-me',
  resave: false,
  saveUninitialized: true
}));

// --- our own identity ---
const sp = saml.ServiceProvider({
  entityID: 'http://localhost:3000/metadata',
  assertionConsumerService: [
    {
      Binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      Location: 'http://localhost:3000/acs'
    },
    {
      Binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      Location: 'http://localhost:3000/acs'
    }
  ],
  authnRequestsSigned: false,
  wantAssertionsSigned: true,
  nameIDFormat: [
    'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'
  ]
});

// --- identity we trust
async function getIdentityProvider() {
  const response = await fetch(`${IDP}/metadata`);
  if (!response.ok) {
    throw new Error(
      `Failed to fetch IdP metadata: ${response.status} ${response.statusText}`
    );
  }

  const metadata = await response.text();
  return saml.IdentityProvider({
    metadata
  });
}

// --- web endpoints
app.get('/metadata', (req, res) => {
  res.header('Content-Type', 'text/xml').send(sp.getMetadata());
});

app.get('/', (req, res) => {
  if (req.session.user)
    return res.redirect('/dashboard');

  res.render('home.ejs');
});

app.get('/login', async (req, res) => {
  const idp = await getIdentityProvider()
  const { context, id } = sp.createLoginRequest(idp, 'redirect');
  // "context" here is the full redirect URL: IdP's SSO endpoint + encoded AuthnRequest
  res.redirect(context);
});

app.get('/acs', async (req, res) => {
  try {
    const idp = await getIdentityProvider()
    const { extract } = await sp.parseLoginResponse(idp, 'redirect', req);

    // "extract" contains the verified NameID and attributes from the assertion.
    // If the signature or conditions were invalid, parseLoginResponse would have thrown.
    req.session.user = {
      email: extract.nameID,
      displayName: extract.attributes.displayName,
      role: extract.attributes.role,
      isAdmin: extract.attributes.role === 'admin'
    };

    res.redirect('/dashboard');
  } catch (err) {
    console.error('Failed to validate SAML response:', err);
    res.status(400).send(`
      <h3>SAML validation failed</h3>
      <pre>${err.message}</pre>
      <a href="/">Back to home</a>
    `);
  }
});

app.get('/dashboard', (req, res) => {
  if (!req.session.user) return res.redirect('/');
  res.render('dashboard.ejs', {
    user: req.session.user
  });
});

app.get('/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/'));
});

// --- start the app
app.listen(PORT, () => {
  console.log(`Service provider running on port ${PORT}`)
});
