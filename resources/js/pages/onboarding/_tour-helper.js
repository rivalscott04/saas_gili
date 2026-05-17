/**
 * Shepherd.js wrapper for per-page onboarding tours (Phase E).
 *
 * Config: JSON in <script type="application/json" id="onboarding-tour-config-{pageId}">
 * Replay: window.dispatchEvent(new CustomEvent('onboarding:replay-tour'))
 */
(function (window, document) {
    'use strict';

    var STORAGE_PREFIX = 'gili_onboarding_tour_seen:';

    function getShepherd() {
        return window.Shepherd;
    }

    function storageKey(pageId) {
        return STORAGE_PREFIX + pageId;
    }

    function hasSeen(pageId) {
        try {
            return window.localStorage.getItem(storageKey(pageId)) === '1';
        } catch (e) {
            return false;
        }
    }

    function markSeen(pageId) {
        try {
            window.localStorage.setItem(storageKey(pageId), '1');
        } catch (e) {
            /* ignore quota / private mode */
        }
    }

    function clearSeen(pageId) {
        try {
            window.localStorage.removeItem(storageKey(pageId));
        } catch (e) {
            /* ignore */
        }
    }

    function shouldAutoStart(pageId, config) {
        if (config && config.forceStart) {
            return true;
        }

        var params = new URLSearchParams(window.location.search);
        if (params.get('onboarding_tour') === '1' || params.get('replay_tour') === '1') {
            return true;
        }

        if (config && config.autoStart === true) {
            return true;
        }

        if (config && config.autoStart === false) {
            return false;
        }

        return !hasSeen(pageId);
    }

    function resolveElement(selector) {
        if (!selector) {
            return null;
        }

        try {
            return document.querySelector(selector);
        } catch (e) {
            return null;
        }
    }

    function clickTab(tabId) {
        if (!tabId) {
            return Promise.resolve();
        }

        var tab = document.getElementById(tabId);
        if (!tab) {
            return Promise.resolve();
        }

        tab.click();

        return new Promise(function (resolve) {
            window.setTimeout(resolve, 300);
        });
    }

    function buildStep(stepDef, labels) {
        var selector = stepDef.attachTo
            || (stepDef.target ? '[data-onboarding="' + stepDef.target + '"]' : null);
        var element = resolveElement(selector);

        if (!element) {
            return null;
        }

        var buttons = [];

        if (stepDef.showBack !== false && labels.back) {
            buttons.push({
                text: labels.back,
                classes: 'btn btn-light btn-sm',
                action: function () {
                    return this.back();
                },
            });
        }

        buttons.push({
            text: stepDef.isLast ? labels.done : labels.next,
            classes: 'btn btn-primary btn-sm',
            action: function () {
                if (stepDef.isLast) {
                    return this.complete();
                }

                return this.next();
            },
        });

        return {
            id: stepDef.id || stepDef.target || selector,
            title: stepDef.title || '',
            text: stepDef.text || '',
            attachTo: {
                element: element,
                on: stepDef.on || 'bottom',
            },
            canClickTarget: false,
            scrollTo: { behavior: 'smooth', block: 'center' },
            beforeShowPromise: function () {
                return clickTab(stepDef.activateTab || null);
            },
            buttons: buttons,
        };
    }

    function createTour(pageId, config) {
        var Shepherd = getShepherd();
        if (!Shepherd) {
            console.warn('[GiliOnboardingTour] Shepherd.js is not loaded.');

            return null;
        }

        var labels = Object.assign(
            {
                next: 'Next',
                back: 'Back',
                done: 'Done',
            },
            config.labels || {},
        );

        var rawSteps = config.steps || [];
        var steps = [];

        rawSteps.forEach(function (stepDef, index) {
            var def = Object.assign({}, stepDef);
            if (index === rawSteps.length - 1) {
                def.isLast = true;
            }

            var built = buildStep(def, labels);
            if (built) {
                steps.push(built);
            }
        });

        if (!steps.length) {
            return null;
        }

        var tour = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                cancelIcon: { enabled: true },
                classes: 'gili-onboarding-tour-step shadow',
                scrollTo: { behavior: 'smooth', block: 'center' },
            },
        });

        steps.forEach(function (step) {
            tour.addStep(step);
        });

        tour.on('complete', function () {
            markSeen(pageId);
        });

        tour.on('cancel', function () {
            markSeen(pageId);
        });

        return tour;
    }

    function start(pageId, config) {
        var tour = createTour(pageId, config);
        if (!tour) {
            return null;
        }

        tour.start();

        return tour;
    }

    function bootFromConfig(configElementId) {
        var configEl = document.getElementById(configElementId);
        if (!configEl) {
            return;
        }

        var config;
        try {
            config = JSON.parse(configEl.textContent || '{}');
        } catch (e) {
            console.warn('[GiliOnboardingTour] Invalid tour config JSON.', e);

            return;
        }

        var pageId = config.pageId;
        if (!pageId) {
            return;
        }

        var activeTour = null;

        function launch(force) {
            if (activeTour && activeTour.isActive && activeTour.isActive()) {
                activeTour.cancel();
            }

            if (force) {
                clearSeen(pageId);
            }

            var launchConfig = Object.assign({}, config, { forceStart: !!force });
            if (!shouldAutoStart(pageId, launchConfig) && !force) {
                return;
            }

            activeTour = start(pageId, launchConfig);
        }

        if (shouldAutoStart(pageId, config)) {
            window.requestAnimationFrame(function () {
                window.setTimeout(function () {
                    launch(false);
                }, 450);
            });
        }

        window.addEventListener('onboarding:replay-tour', function () {
            launch(true);
        });
    }

    window.GiliOnboardingTour = {
        hasSeen: hasSeen,
        markSeen: markSeen,
        clearSeen: clearSeen,
        start: start,
        bootFromConfig: bootFromConfig,
    };
})(window, document);
