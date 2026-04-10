(function () {
  function getFieldValue(field) {
    if (field.type === 'checkbox') {
      return field.checked ? '1' : '';
    }

    return field.value || '';
  }

  function getStatusNode(form) {
    if (!form) {
      return null;
    }

    var node = form.querySelector('.myseo-live-status');
    if (!node) {
      node = document.createElement('div');
      node.className = 'myseo-live-status';
      node.setAttribute('aria-live', 'polite');
      form.insertBefore(node, form.firstChild);
    }

    return node;
  }

  function setStatus(form, text, isError) {
    var node = getStatusNode(form);
    if (!node) {
      return;
    }

    node.textContent = text;
    node.classList.toggle('is-error', !!isError);
    node.classList.toggle('is-success', !isError && !!text);
  }

  function postAjax(payload) {
    payload._ajax_nonce = myseoAdmin.nonce;
    return fetch(myseoAdmin.ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: new URLSearchParams(payload).toString()
    }).then(function (response) {
      return response.json();
    });
  }

  function updateGoogleUi(data) {
    var credentialsCard = document.getElementById('myseo-google-credentials-card');
    var connectionCard = document.getElementById('myseo-google-connection-card');
    var readyWrap = document.getElementById('myseo-google-auth-ready');
    var missingWrap = document.getElementById('myseo-google-auth-missing');
    var authButton = document.getElementById('myseo-google-auth-button');
    var manualCodeForm = document.getElementById('myseo-google-manual-code-form');

    if (credentialsCard) {
      credentialsCard.style.display = data.oauthMode === 'developer' ? '' : 'none';
    }
    if (connectionCard) {
      connectionCard.style.display = data.oauthMode === 'product' ? '' : 'none';
    }
    if (manualCodeForm) {
      manualCodeForm.style.display = data.oauthMode === 'developer' ? '' : 'none';
    }
    if (readyWrap) {
      readyWrap.style.display = data.clientReady ? '' : 'none';
    }
    if (missingWrap) {
      missingWrap.style.display = data.clientReady ? 'none' : '';
    }
    if (authButton && data.authUrl) {
      authButton.setAttribute('href', data.authUrl);
    }
  }

  function parseSettingKey(name) {
    var match = /^myseo_settings\[(.+)\]$/.exec(name || '');
    return match ? match[1] : '';
  }

  function parseModuleKey(name) {
    var match = /^myseo_modules\[(.+)\]$/.exec(name || '');
    return match ? match[1] : '';
  }

  function saveLiveField(field) {
    var form = field.form;
    if (!form || !form.classList.contains('myseo-live-form')) {
      return;
    }

    var scope = form.getAttribute('data-myseo-live-scope');
    var payload;

    if (scope === 'settings') {
      var key = parseSettingKey(field.name);
      if (!key) {
        return;
      }
      payload = {
        action: 'myseo_save_setting',
        key: key,
        value: getFieldValue(field)
      };
    } else if (scope === 'modules') {
      var module = parseModuleKey(field.name);
      if (!module) {
        return;
      }
      payload = {
        action: 'myseo_save_module',
        module: module,
        enabled: field.checked ? '1' : ''
      };
    } else if (scope === 'google') {
      if (!field.name || field.name.indexOf('myseo_google_') !== 0) {
        return;
      }
      payload = {
        action: 'myseo_save_google_setting',
        key: field.name.replace(/^myseo_/, ''),
        value: getFieldValue(field)
      };
    } else {
      return;
    }

    setStatus(form, 'Saving...', false);

    postAjax(payload)
      .then(function (response) {
        if (!response || !response.success) {
          setStatus(form, (response && response.data && response.data.message) || 'Save failed.', true);
          return;
        }

        setStatus(form, (response.data && response.data.message) || 'Saved.', false);
        if (scope === 'google' && response.data) {
          updateGoogleUi(response.data);
        }
      })
      .catch(function () {
        setStatus(form, 'Save failed.', true);
      });
  }

  function saveLiveForm(form) {
    var fields = Array.prototype.slice.call(
      form.querySelectorAll('input[name], select[name], textarea[name]')
    ).filter(function (field) {
      if (!field.name) {
        return false;
      }

      if (field.type === 'submit' || field.type === 'button' || field.type === 'hidden') {
        return false;
      }

      if (field.name === '_wpnonce' || field.name === '_wp_http_referer') {
        return false;
      }

      return true;
    });

    if (!fields.length) {
      setStatus(form, 'Nothing to save.', false);
      return;
    }

    setStatus(form, 'Saving...', false);

    Promise.allSettled(fields.map(function (field) {
      return new Promise(function (resolve) {
        var scope = form.getAttribute('data-myseo-live-scope');
        var payload;

        if (scope === 'settings') {
          var key = parseSettingKey(field.name);
          if (!key) {
            resolve({ skipped: true });
            return;
          }
          payload = {
            action: 'myseo_save_setting',
            key: key,
            value: getFieldValue(field)
          };
        } else if (scope === 'modules') {
          var module = parseModuleKey(field.name);
          if (!module) {
            resolve({ skipped: true });
            return;
          }
          payload = {
            action: 'myseo_save_module',
            module: module,
            enabled: field.checked ? '1' : ''
          };
        } else if (scope === 'google') {
          if (!field.name || field.name.indexOf('myseo_google_') !== 0) {
            resolve({ skipped: true });
            return;
          }
          payload = {
            action: 'myseo_save_google_setting',
            key: field.name.replace(/^myseo_/, ''),
            value: getFieldValue(field)
          };
        } else {
          resolve({ skipped: true });
          return;
        }

        postAjax(payload)
          .then(function (response) {
            resolve(response);
          })
          .catch(function () {
            resolve({ success: false, data: { message: 'Save failed.' } });
          });
      });
    })).then(function (results) {
      var failed = results.find(function (result) {
        return result.status === 'fulfilled' && result.value && result.value.success === false;
      });
      var googleResponse = results.find(function (result) {
        return result.status === 'fulfilled' && result.value && result.value.success && result.value.data && typeof result.value.data.oauthMode !== 'undefined';
      });

      if (googleResponse) {
        updateGoogleUi(googleResponse.value.data);
      }

      if (failed) {
        setStatus(form, (failed.value.data && failed.value.data.message) || 'Save failed.', true);
        return;
      }

      setStatus(form, 'Saved.', false);
    });
  }

  function openGoogleAuthPopup(event) {
    var trigger = event.target.closest('[data-myseo-google-auth]');
    if (!trigger) {
      return;
    }

    event.preventDefault();
    var url = trigger.getAttribute('href');
    if (!url) {
      return;
    }

    var width = 720;
    var height = 760;
    var left = window.screenX + Math.max(0, (window.outerWidth - width) / 2);
    var top = window.screenY + Math.max(0, (window.outerHeight - height) / 2);

    window.open(
      url,
      'myseo-google-auth',
      'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes'
    );
  }

  document.addEventListener('change', function (event) {
    var field = event.target;
    if (!field || !field.form || !field.form.classList.contains('myseo-live-form')) {
      return;
    }

    saveLiveField(field);
  });

  document.addEventListener('blur', function (event) {
    var field = event.target;
    if (!field || !field.form || !field.form.classList.contains('myseo-live-form')) {
      return;
    }

    if (field.tagName === 'INPUT' || field.tagName === 'TEXTAREA') {
      saveLiveField(field);
    }
  }, true);

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.classList.contains('myseo-live-form')) {
      return;
    }

    event.preventDefault();
    saveLiveForm(form);
  });

  document.addEventListener('click', openGoogleAuthPopup);
})();
