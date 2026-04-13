/* ════════════════════════════════════════
   REGEX PATTERNS
════════════════════════════════════════ */
const PATTERNS = {
  name:    /^[A-Za-zÀ-ÖØ-öø-ÿ\s\-'.]+$/,
  address: /^[A-Za-zÀ-ÖØ-öø-ÿ0-9\s#,.\-/']+$/,
};

/* ════════════════════════════════════════
   NAME VALIDATOR
════════════════════════════════════════ */
function validateName(value) {
  if (!value.trim()) return 'required';
  return 'ok';
}

const NAME_ERR = {
  required: 'Please enter your full name.',
};

/* ════════════════════════════════════════
   ADDRESS VALIDATOR
════════════════════════════════════════ */
function validateAddress(value) {
  if (!value.trim()) return 'required';
  return 'ok';
}

const ADDR_ERR = {
  required: 'Please enter your address.',
};

/* ════════════════════════════════════════
   AUTO-CAPITALIZE
════════════════════════════════════════ */
function formatNameInput(input) {
  const val = input.value;
  const formatted = val
    .split(/(\s+)/)
    .map(part => {
      if (part.trim() === '') return part;
      return part.charAt(0).toUpperCase() + part.slice(1).toLowerCase();
    })
    .join('');
  input.value = formatted;
}

/* ════════════════════════════════════════
   CASCADING DROPDOWN
════════════════════════════════════════ */
(function initCascade() {
  const BASE = 'https://psgc.cloud/api';

  const REGION_GROUPS = {
    'Metro Manila':  ['National Capital'],
    'North Luzon':   ['Region I', 'Region II', 'Region III', 'Cordillera'],
    'South Luzon':   ['Region IV', 'Region V', 'MIMAROPA'],
    'Visayas':       ['Region VI', 'Region VII', 'Region VIII'],
    'Mindanao':      ['Region IX', 'Region X', 'Region XI', 'Region XII', 'Region XIII', 'BARMM', 'Bangsamoro'],
  };

  function formatCityName(raw) {
    const decoded = raw
      .replace(/Ã±/g, 'ñ')
      .replace(/Ã©/g, 'é')
      .replace(/Ã¡/g, 'á')
      .replace(/Ã­/g, 'í')
      .replace(/Ã³/g, 'ó')
      .replace(/Ãº/g, 'ú')
      .replace(/Ã'/g, 'Ñ');
    const trimmed = decoded.trim();
    const match   = trimmed.match(/^City\s+of\s+(.+)$/i);
    return match ? `${match[1].trim()} City` : trimmed;
  }

  const selRegion   = document.getElementById('ff_region');
  const selProvince = document.getElementById('ff_province');
  const selCity     = document.getElementById('ff_city');
  const selBarangay = document.getElementById('ff_barangay');

  const wrapRegion   = selRegion?.closest('.select-wrap');
  const wrapProvince = selProvince?.closest('.select-wrap');
  const wrapCity     = selCity?.closest('.select-wrap');
  const wrapBarangay = selBarangay?.closest('.select-wrap');

  if (!selRegion || !selProvince || !selCity || !selBarangay) return;

  async function psgcFetch(url, wrapEl) {
    wrapEl?.classList.add('loading');
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (err) {
      console.error('[PSGC]', err);
      return null;
    } finally {
      wrapEl?.classList.remove('loading');
    }
  }

  function resetSelect(sel, placeholder) {
    sel.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
    sel.classList.remove('has-value');
  }

  function disableSelects(...selects) {
    selects.forEach(s => { s.disabled = true; s.classList.remove('has-value'); });
  }

  async function populateRegions() {
    resetSelect(selRegion, 'Loading regions…');
    selRegion.disabled = true;
    disableSelects(selProvince, selCity, selBarangay);

    const allRegions = await psgcFetch(`${BASE}/regions`, wrapRegion);
    if (!allRegions) {
      resetSelect(selRegion, 'Failed to load — reload page');
      return;
    }

    window._psgcAllRegions = allRegions;

    resetSelect(selRegion, 'Select region…');
    Object.keys(REGION_GROUPS).forEach(groupLabel => {
      const opt = document.createElement('option');
      opt.value = groupLabel;
      opt.dataset.name = groupLabel;
      opt.dataset.isNcr = groupLabel === 'Metro Manila' ? '1' : '0';
      opt.textContent = groupLabel;
      selRegion.appendChild(opt);
    });

    selRegion.disabled = false;
  }

  async function onRegionChange() {
    const selectedGroup = selRegion.value;
    const isNCR = selRegion.options[selRegion.selectedIndex]?.dataset.isNcr === '1';

    resetSelect(selProvince, 'Loading provinces…');
    resetSelect(selCity,     'Select city / municipality…');
    resetSelect(selBarangay, 'Select barangay…');
    disableSelects(selProvince, selCity, selBarangay);

    document.getElementById('hid_region').value   = selectedGroup;
    document.getElementById('hid_province').value = '';
    document.getElementById('hid_city').value     = '';
    document.getElementById('hid_barangay').value = '';

    selRegion.classList.add('has-value');
    clearErr('ff_region', 'err_region');

    if (!selectedGroup) return;

    if (isNCR) {
      resetSelect(selProvince, 'N/A (Metro Manila)');
      selProvince.disabled = true;
      document.getElementById('hid_province').value = 'Metro Manila';

      const ncrRegion = window._psgcAllRegions?.find(r => r.name.includes('National Capital'));
      if (ncrRegion) {
        const cities = await psgcFetch(`${BASE}/regions/${ncrRegion.code}/cities-municipalities`, wrapCity);
        populateCities(cities);
      }
      return;
    }

    const keywords = REGION_GROUPS[selectedGroup];
    const matchingRegionCodes = (window._psgcAllRegions || [])
      .filter(r => keywords.some(kw => r.name.includes(kw)))
      .map(r => r.code);

    if (!matchingRegionCodes.length) {
      resetSelect(selProvince, 'No provinces found');
      return;
    }

    const provincePromises = matchingRegionCodes.map(code =>
      psgcFetch(`${BASE}/regions/${code}/provinces`, wrapProvince)
    );

    const results = await Promise.all(provincePromises);
    const allProvinces = results
      .filter(arr => Array.isArray(arr))
      .flat()
      .filter((v, i, a) => a.findIndex(p => p.code === v.code) === i);

    if (!allProvinces.length) {
      resetSelect(selProvince, 'No provinces found');
      return;
    }

    resetSelect(selProvince, 'Select province…');
    allProvinces
      .sort((a, b) => a.name.localeCompare(b.name))
      .forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.code;
        opt.dataset.name = p.name;
        opt.textContent = p.name;
        selProvince.appendChild(opt);
      });

    selProvince.disabled = false;
  }

  async function onProvinceChange() {
    const provinceCode = selProvince.value;
    const selectedOpt  = selProvince.options[selProvince.selectedIndex];
    const provinceName = selectedOpt?.dataset.name ?? selProvince.value;

    resetSelect(selCity,     'Loading cities…');
    resetSelect(selBarangay, 'Select barangay…');
    disableSelects(selCity, selBarangay);

    document.getElementById('hid_province').value = provinceName;
    document.getElementById('hid_city').value     = '';
    document.getElementById('hid_barangay').value = '';

    selProvince.classList.add('has-value');
    clearErr('ff_province', 'err_province');

    if (!provinceCode) return;

    const cities = await psgcFetch(`${BASE}/provinces/${provinceCode}/cities-municipalities`, wrapCity);
    populateCities(cities);
  }

  function populateCities(cities) {
    if (!cities || cities.length === 0) {
      resetSelect(selCity, 'No cities found');
      return;
    }
    resetSelect(selCity, 'Select city / municipality…');
    cities
      .map(c => ({ ...c, displayName: formatCityName(c.name) }))
      .sort((a, b) => a.displayName.localeCompare(b.displayName))
      .forEach(c => {
        const opt = document.createElement('option');
        opt.value        = c.code;
        opt.dataset.name = c.displayName;
        opt.textContent  = c.displayName;
        selCity.appendChild(opt);
      });
    selCity.disabled = false;
  }

  async function onCityChange() {
    const cityCode    = selCity.value;
    const selectedOpt = selCity.options[selCity.selectedIndex];
    const cityName    = selectedOpt?.dataset.name ?? selCity.value;

    resetSelect(selBarangay, 'Loading barangays…');
    selBarangay.disabled = true;

    document.getElementById('hid_city').value     = cityName;
    document.getElementById('hid_barangay').value = '';

    selCity.classList.add('has-value');
    clearErr('ff_city', 'err_city');

    if (!cityCode) return;

    const barangays = await psgcFetch(
      `${BASE}/cities-municipalities/${cityCode}/barangays`,
      wrapBarangay
    );

    if (!barangays || barangays.length === 0) {
      resetSelect(selBarangay, 'No barangays found');
      return;
    }

    resetSelect(selBarangay, 'Select barangay…');
    barangays
      .sort((a, b) => a.name.localeCompare(b.name))
      .forEach(b => {
        const opt = document.createElement('option');
        opt.value        = b.name;
        opt.dataset.name = b.name;
        opt.textContent  = b.name;
        selBarangay.appendChild(opt);
      });

    selBarangay.disabled = false;
  }

  function onBarangayChange() {
    document.getElementById('hid_barangay').value = selBarangay.value;
    selBarangay.classList.add('has-value');
    clearErr('ff_barangay', 'err_barangay');
  }

  selRegion.addEventListener('change',   onRegionChange);
  selProvince.addEventListener('change', onProvinceChange);
  selCity.addEventListener('change',     onCityChange);
  selBarangay.addEventListener('change', onBarangayChange);

  populateRegions();
})();

/* ════════════════════════════════════════
   PAYMENT METHOD TOGGLE
════════════════════════════════════════ */
function selectPay(el, type) {
  document.querySelectorAll('.pay-opt').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('paymentMethodInput').value = type;
  document.getElementById('cardFields').classList.toggle('show', type === 'card');
}

/* ════════════════════════════════════════
   SAVED ADDRESS SELECTION
   Uses data-* attributes — no inline param passing needed
════════════════════════════════════════ */
function selectAddrFromCard(el) {
  // Deselect all cards
  document.querySelectorAll('.addr-card').forEach(c => c.classList.remove('selected'));

  // Select this card
  el.classList.add('selected');

  // Collapse the manual form
  document.getElementById('manualForm').classList.add('collapsed');
  document.getElementById('addrNewToggle').classList.remove('selected');

  // Read data attributes
  const fullName = el.dataset.fullname  || '';
  const address  = el.dataset.street    || '';
  const barangay = el.dataset.barangay  || '';
  const city     = el.dataset.city      || '';
  const province = el.dataset.province  || '';
  const region   = el.dataset.region    || '';
  const zip      = el.dataset.zip       || '';
  const phone    = el.dataset.phone     || '';

  // Populate hidden fields
  document.getElementById('hid_fullname').value  = fullName;
  document.getElementById('hid_address').value   = address;
  document.getElementById('hid_barangay').value  = barangay;
  document.getElementById('hid_city').value      = city;
  document.getElementById('hid_province').value  = province;
  document.getElementById('hid_region').value    = region;
  document.getElementById('hid_zip').value       = zip;
  document.getElementById('ff_phone_full').value = '+63' + phone;
}

function toggleNewAddress() {
  document.querySelectorAll('.addr-card').forEach(c => c.classList.remove('selected'));
  document.getElementById('addrNewToggle').classList.add('selected');
  document.getElementById('manualForm').classList.remove('collapsed');

  // Clear all hidden fields so manual input takes over
  document.getElementById('hid_fullname').value  = '';
  document.getElementById('hid_address').value   = '';
  document.getElementById('hid_barangay').value  = '';
  document.getElementById('hid_city').value      = '';
  document.getElementById('hid_province').value  = '';
  document.getElementById('hid_region').value    = '';
  document.getElementById('hid_zip').value       = '';
  document.getElementById('ff_phone_full').value = '';
}

/* ════════════════════════════════════════
   INITIALIZE ADDRESS CARD LISTENERS
   Attaches click handlers to all saved address cards
════════════════════════════════════════ */
function initAddressCardListeners() {
  // Get all saved address cards (excluding the "new address" card)
  const addrCards = document.querySelectorAll('.addr-card:not(.addr-card-new)');
  
  addrCards.forEach(card => {
    card.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      selectAddrFromCard(this);
    });
  });

  // Attach listener to "use different address" card
  const newAddrCard = document.getElementById('addrNewToggle');
  if (newAddrCard) {
    newAddrCard.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      toggleNewAddress();
    });
  }
}

// Initialize address listeners when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAddressCardListeners);
} else {
  initAddressCardListeners();
}

/* ════════════════════════════════════════
   PHONE FORMATTING
════════════════════════════════════════ */
function formatPhone(input) {
  let digits = input.value.replace(/\D/g, '').slice(0, 10);
  let f = '';
  if      (digits.length <= 3) f = digits;
  else if (digits.length <= 6) f = digits.slice(0,3) + ' ' + digits.slice(3);
  else                          f = digits.slice(0,3) + ' ' + digits.slice(3,6) + ' ' + digits.slice(6);
  input.value = f;
  document.getElementById('ff_phone_full').value = '+63' + digits;
  if (digits.length === 10) clearErr('phoneWrap', 'err_phone');
}

/* ════════════════════════════════════════
   CARD NUMBER FORMATTING
════════════════════════════════════════ */
function formatCardNum(input) {
  let digits = input.value.replace(/\D/g, '').slice(0, 16);
  input.value = digits.replace(/(.{4})/g, '$1 ').trim();
  if (digits.length === 16) clearErr('cc_num', 'err_cc_num');
}

/* ════════════════════════════════════════
   EXPIRY DATE FORMATTING
════════════════════════════════════════ */
function formatExpiry(input) {
  let val = input.value.replace(/\D/g, '').slice(0, 4);
  if (val.length >= 3) val = val.slice(0,2) + '/' + val.slice(2);
  input.value = val;
}

/* ════════════════════════════════════════
   VALIDATION HELPERS
════════════════════════════════════════ */
function showErr(id, errId, msg) {
  const el = document.getElementById(id);
  if (el) el.classList.add('invalid');
  const errEl = document.getElementById(errId);
  if (errEl) {
    if (msg) errEl.innerHTML = `<i class="fas fa-circle-exclamation" style="font-size:10px;"></i> ${msg}`;
    errEl.classList.add('show');
  }
}

function clearErr(id, errId) {
  document.getElementById(id)?.classList.remove('invalid');
  document.getElementById(errId)?.classList.remove('show');
}

/* ════════════════════════════════════════
   LIVE VALIDATION — Full Name
════════════════════════════════════════ */
document.getElementById('ff_fullname')?.addEventListener('input', function () {
  if (this.value.trim()) clearErr('ff_fullname', 'err_fullname');
});

/* ════════════════════════════════════════
   LIVE VALIDATION — Address
════════════════════════════════════════ */
document.getElementById('ff_address')?.addEventListener('input', function () {
  if (this.value.trim()) clearErr('ff_address', 'err_address');
});

/* ════════════════════════════════════════
   LIVE VALIDATION — ZIP & card fields
════════════════════════════════════════ */
document.getElementById('ff_zip')?.addEventListener('input', function () {
  if (this.value.trim()) clearErr('ff_zip', 'err_zip');
});

document.getElementById('cc_name')?.addEventListener('input', function () {
  if (this.value.trim()) clearErr('cc_name', 'err_cc_name');
});

['cc_num', 'cc_exp', 'cc_cvc'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', function () {
    if (this.value.trim()) clearErr(id, 'err_' + id);
  });
});

/* ════════════════════════════════════════
   MAIN FORM VALIDATION ON SUBMIT
════════════════════════════════════════ */
document.getElementById('checkoutForm').addEventListener('submit', function (e) {
  let valid = true;
  const isManualOpen = !document.getElementById('manualForm').classList.contains('collapsed');

  if (isManualOpen) {
    // Full Name
    const fullname = document.getElementById('ff_fullname');
    if (!fullname.value.trim()) {
      showErr('ff_fullname', 'err_fullname', 'Please enter your full name.');
      valid = false;
    } else {
      clearErr('ff_fullname', 'err_fullname');
      document.getElementById('hid_fullname').value = fullname.value.trim();
    }

    // Address
    const addr = document.getElementById('ff_address');
    if (!addr.value.trim()) {
      showErr('ff_address', 'err_address', 'Please enter your address.');
      valid = false;
    } else {
      clearErr('ff_address', 'err_address');
      document.getElementById('hid_address').value = addr.value.trim();
    }

    // Region
    const region = document.getElementById('ff_region');
    if (!region.value) {
      showErr('ff_region', 'err_region', 'Please select a region.');
      valid = false;
    } else clearErr('ff_region', 'err_region');

    // Province — skip for NCR
    const province      = document.getElementById('ff_province');
    const isNCRProvince = province.options[0]?.textContent?.includes('Metro Manila');
    if (!isNCRProvince && !province.value) {
      showErr('ff_province', 'err_province', 'Please select a province.');
      valid = false;
    } else clearErr('ff_province', 'err_province');

    // City
    const city = document.getElementById('ff_city');
    if (!city.value) {
      showErr('ff_city', 'err_city', 'Please select a city.');
      valid = false;
    } else clearErr('ff_city', 'err_city');

    // Barangay
    const barangay = document.getElementById('ff_barangay');
    if (!barangay.value) {
      showErr('ff_barangay', 'err_barangay', 'Please select a barangay.');
      valid = false;
    } else {
      clearErr('ff_barangay', 'err_barangay');
      document.getElementById('hid_barangay').value = barangay.value;
    }

    // ZIP
    const zip = document.getElementById('ff_zip');
    if (!/^\d{4}$/.test(zip.value.trim())) {
      showErr('ff_zip', 'err_zip', 'Please enter a valid 4-digit ZIP code.');
      valid = false;
    } else {
      clearErr('ff_zip', 'err_zip');
      document.getElementById('hid_zip').value = zip.value.trim();
    }

    // Phone
    const phone  = document.getElementById('ff_phone');
    const digits = phone.value.replace(/\D/g, '');
    if (digits.length !== 10) {
      showErr('phoneWrap', 'err_phone', 'Please enter a valid 10-digit phone number.');
      valid = false;
    } else clearErr('phoneWrap', 'err_phone');

  } else {
    // Saved address selected — verify hid_fullname was populated
    const hidFullname = document.getElementById('hid_fullname');
    if (!hidFullname.value.trim()) {
      // Attempt to recover name from selected card
      const selectedCard = document.querySelector('.addr-card.selected:not(#addrNewToggle)');
      if (selectedCard) {
        hidFullname.value = selectedCard.dataset.fullname || '';
      }
      // Still empty — block submission
      if (!hidFullname.value.trim()) {
        valid = false;
      }
    }
  }

  /* ── Card fields ── */
  const payType = document.getElementById('paymentMethodInput').value;
  if (payType === 'card') {

    const ccNum    = document.getElementById('cc_num');
    const ccDigits = ccNum.value.replace(/\D/g, '');
    if (ccDigits.length !== 16) {
      showErr('cc_num', 'err_cc_num', 'Please enter a valid 16-digit card number.');
      valid = false;
    } else clearErr('cc_num', 'err_cc_num');

    const ccExp = document.getElementById('cc_exp');
    if (!/^\d{2}\/\d{2}$/.test(ccExp.value.trim())) {
      showErr('cc_exp', 'err_cc_exp', 'Please enter a valid expiry date.');
      valid = false;
    } else {
      const [mm, yy] = ccExp.value.split('/').map(Number);
      const now      = new Date();
      const expDate  = new Date(2000 + yy, mm - 1);
      if (mm < 1 || mm > 12 || expDate < new Date(now.getFullYear(), now.getMonth())) {
        showErr('cc_exp', 'err_cc_exp', 'Please enter a valid expiry date.');
        valid = false;
      } else clearErr('cc_exp', 'err_cc_exp');
    }

    const ccCvc = document.getElementById('cc_cvc');
    if (!/^\d{3,4}$/.test(ccCvc.value.trim())) {
      showErr('cc_cvc', 'err_cc_cvc', 'Please enter a valid CVC.');
      valid = false;
    } else clearErr('cc_cvc', 'err_cc_cvc');

    const ccName = document.getElementById('cc_name');
    if (!ccName.value.trim()) {
      showErr('cc_name', 'err_cc_name', 'Please enter the name on your card.');
      valid = false;
    } else clearErr('cc_name', 'err_cc_name');
  }

  if (!valid) {
    e.preventDefault();
    const firstErr = document.querySelector('.f-input.invalid, .phone-wrap.invalid, select.invalid');
    firstErr?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});