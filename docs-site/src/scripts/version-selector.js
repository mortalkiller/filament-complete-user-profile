const manifestPromises = new Map();

function normalizeRoot(root) {
  return String(root).replace(/\/+$/, '');
}

function isManifest(manifest) {
  return manifest?.schema === 1
    && manifest.channels
    && typeof manifest.channels === 'object'
    && !Array.isArray(manifest.channels);
}

function channelFromPath(pathname, root) {
  const normalizedRoot = normalizeRoot(root);
  const prefix = `${normalizedRoot}/`;

  if (!String(pathname).startsWith(prefix)) {
    return null;
  }

  const segment = String(pathname).slice(prefix.length).split('/')[0];

  return /^(0|[1-9]\d*)\.x$/.test(segment) ? segment : null;
}

export function buildVersionOptions(manifest, root) {
  if (!isManifest(manifest)) {
    return [];
  }

  const normalizedRoot = normalizeRoot(root);
  const entries = Object.entries(manifest.channels)
    .filter(([major, tag]) => /^(0|[1-9]\d*)\.x$/.test(major)
      && typeof tag === 'string'
      && /^v\d+\.\d+\.\d+$/.test(tag))
    .sort(([left], [right]) => Number.parseInt(right) - Number.parseInt(left));

  const latestMajor = entries.find(([, tag]) => tag === manifest.latest)?.[0] ?? null;

  return entries.map(([major, tag]) => {
    const isLatest = major === latestMajor;

    return {
      major,
      tag,
      isLatest,
      label: `${major} (${tag})${isLatest ? ' — Latest' : ''}`,
      value: isLatest ? `${normalizedRoot}/` : `${normalizedRoot}/${major}/`,
    };
  });
}

export function selectedVersionValue(options, pathname, root) {
  if (!options.length) {
    return null;
  }

  const channel = channelFromPath(pathname, root);

  if (channel) {
    const option = options.find((entry) => entry.major === channel);

    if (option) {
      return option.value;
    }
  }

  return options.find((entry) => entry.isLatest)?.value ?? options[0].value;
}

function syncVersionSelection(select, pathname) {
  const root = select.dataset.docsRoot;

  if (!root) {
    return;
  }

  const normalizedRoot = normalizeRoot(root);
  const channel = channelFromPath(pathname, normalizedRoot);
  const latestMajor = select.dataset.docsLatestMajor || null;
  const candidate = channel && channel !== latestMajor
    ? `${normalizedRoot}/${channel}/`
    : `${normalizedRoot}/`;
  const values = Array.from(select.options, (option) => option.value);

  select.value = values.includes(candidate) ? candidate : `${normalizedRoot}/`;
}

function renderVersionOptions(select, options, pathname) {
  const documentObj = select.ownerDocument ?? globalThis.document;

  if (!documentObj) {
    return;
  }

  const optionElements = options.map((entry) => {
    const option = documentObj.createElement('option');
    option.value = entry.value;
    option.textContent = entry.label;
    option.dataset.docsMajor = entry.major;

    if (entry.isLatest) {
      option.dataset.docsLatest = 'true';
    }

    return option;
  });

  select.replaceChildren(...optionElements);
  select.dataset.docsLatestMajor = options.find((entry) => entry.isLatest)?.major ?? '';
  select.dataset.docsVersionReady = 'true';
  select.dataset.docsVersionLoading = 'false';
  select.disabled = false;
  syncVersionSelection(select, pathname);
}

function manifestFor(root, fetchImpl) {
  const normalizedRoot = normalizeRoot(root);

  if (!manifestPromises.has(normalizedRoot)) {
    const request = fetchImpl(`${normalizedRoot}/versions.json`, { cache: 'no-cache' })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Version manifest unavailable.');
        }

        return response.json();
      })
      .catch((error) => {
        manifestPromises.delete(normalizedRoot);
        throw error;
      });

    manifestPromises.set(normalizedRoot, request);
  }

  return manifestPromises.get(normalizedRoot);
}

export async function initializeVersionSelect(
  select,
  {
    fetchImpl = globalThis.fetch,
    locationObj = globalThis.location,
  } = {},
) {
  const root = select.dataset.docsRoot;

  if (!root || !fetchImpl || !locationObj) {
    return;
  }

  if (select.dataset.docsVersionReady === 'true') {
    syncVersionSelection(select, locationObj.pathname);

    return;
  }

  if (select.dataset.docsVersionLoading === 'true') {
    return;
  }

  select.dataset.docsVersionLoading = 'true';

  try {
    const manifest = await manifestFor(root, fetchImpl);
    const options = buildVersionOptions(manifest, root);

    if (!options.length) {
      select.dataset.docsVersionLoading = 'false';

      return;
    }

    renderVersionOptions(select, options, locationObj.pathname);

    if (select.dataset.docsVersionChangeBound !== 'true') {
      select.dataset.docsVersionChangeBound = 'true';
      select.addEventListener('change', () => {
        locationObj.assign(select.value);
      });
    }
  } catch {
    select.dataset.docsVersionLoading = 'false';
    // Development and unpublished versions do not have a public manifest.
  }
}

export async function initializeVersionPickers(
  {
    documentObj = globalThis.document,
    fetchImpl = globalThis.fetch,
    locationObj = globalThis.location,
  } = {},
) {
  if (!documentObj) {
    return;
  }

  await Promise.all(
    Array.from(documentObj.querySelectorAll('select[data-docs-root]'))
      .map((select) => initializeVersionSelect(select, { fetchImpl, locationObj })),
  );
}

export function installVersionPicker(
  {
    documentObj = globalThis.document,
    fetchImpl = globalThis.fetch,
    locationObj = globalThis.location,
  } = {},
) {
  if (!documentObj) {
    return Promise.resolve();
  }

  const run = () => initializeVersionPickers({ documentObj, fetchImpl, locationObj });
  const firstRun = run();

  if (documentObj.documentElement?.dataset.docsVersionPickerInstalled !== 'true') {
    documentObj.documentElement.dataset.docsVersionPickerInstalled = 'true';
    documentObj.addEventListener('astro:page-load', run);
  }

  return firstRun;
}

export function clearVersionManifestCache() {
  manifestPromises.clear();
}
