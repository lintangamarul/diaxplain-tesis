    <script>
        (() => {
            // 1. Conditional Popover API polyfill (only when native popover is missing)
            if (!Object.hasOwn(HTMLElement.prototype, 'popover')) {
                const s = document.createElement('script');
                s.src = 'https://unpkg.com/@oddbird/popover-polyfill@latest/dist/popover.min.js';
                s.defer = true;
                document.head.appendChild(s);
            }

            // 2. Deferred Form Validation ARIA Synchronization & Fallback
            const dirtyState = new WeakMap();
            const syncAriaAndFallback = (el) => {
                if (!el || typeof el.checkValidity !== 'function') return;
                const isInvalid = el.matches?.(':user-invalid') || !el.checkValidity();
                el.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
                if (!CSS.supports('selector(:user-invalid)')) {
                    el.classList.toggle('user-invalid-fallback', !el.checkValidity());
                }
            };
            document.addEventListener('blur', (e) => {
                const input = e.target;
                if (!input || !input.checkValidity) return;
                const st = dirtyState.get(input) || { interacted: false };
                if (st.interacted) syncAriaAndFallback(input);
            }, true);
            document.addEventListener('input', (e) => {
                const input = e.target;
                if (!input || !input.checkValidity) return;
                dirtyState.set(input, { interacted: true });
                if (input.getAttribute('aria-invalid') === 'true') {
                    syncAriaAndFallback(input);
                }
            });

            // 3. Light-dismiss fallback for <dialog closedby="any"> when unsupported (Safari)
            if (!('closedBy' in HTMLDialogElement.prototype)) {
                const isOutside = (e, dialog) => {
                    const r = dialog.getBoundingClientRect();
                    return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
                };
                document.querySelectorAll('dialog[closedby="any"]').forEach((dialog) => {
                    let pressedOutside = false;
                    dialog.addEventListener('pointerdown', (e) => { pressedOutside = isOutside(e, dialog); });
                    dialog.addEventListener('pointerup', (e) => {
                        if (pressedOutside && isOutside(e, dialog)) dialog.close();
                    });
                });
            }

            // 4. Native Mobile Navigation Drawer (<dialog class="Drawer">)
            const drawer = document.getElementById('mobile-nav-drawer');
            const openBtn = document.getElementById('mobile-drawer-open');
            const closeBtn = document.getElementById('mobile-drawer-close');
            if (drawer && openBtn) {
                const scroller = drawer.querySelector('.Drawer-scroller');
                const sheet = drawer.querySelector('.Drawer-sheet');

                const openDrawer = async () => {
                    drawer.showModal();
                    if (!CSS.supports('scroll-initial-target', 'nearest')) {
                        scroller.scrollTo({ left: scroller.offsetWidth, behavior: 'instant' });
                        await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
                    }
                    scroller.scrollTo({ left: 0, behavior: 'auto' });
                };

                const closeDrawer = () => {
                    scroller.scrollTo({ left: scroller.offsetWidth, behavior: 'auto' });
                };

                if (!CSS.supports('animation-timeline: scroll()')) {
                    scroller.addEventListener('scroll', () => {
                        const w = sheet.offsetWidth || 280;
                        const ratio = Math.max(0, Math.min(1, 1 - scroller.scrollLeft / w));
                        drawer.style.setProperty('--drawer-backdrop', String(ratio));
                    });
                }

                const visibleThreshold = Math.min(0.05, 1 / Math.max(window.innerWidth, 320));
                const observer = new IntersectionObserver((entries) => {
                    const entry = entries.at(-1);
                    if (!entry) return;
                    if (entry.intersectionRatio < visibleThreshold && drawer.open) {
                        drawer.close();
                        openBtn.setAttribute('aria-expanded', 'false');
                    }
                    if (entry.intersectionRatio >= 0.99 && drawer.open) {
                        openBtn.setAttribute('aria-expanded', 'true');
                    }
                }, { root: drawer, threshold: [visibleThreshold, 1] });
                observer.observe(sheet);

                openBtn.addEventListener('click', openDrawer);
                if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
                drawer.addEventListener('click', (e) => {
                    if (!sheet.contains(e.target)) closeDrawer();
                });
                drawer.addEventListener('cancel', (e) => {
                    if (e.cancelable) {
                        e.preventDefault();
                        closeDrawer();
                    }
                });
            }

            // 5. Accessible Tabs Controller (focusgroup fallback + hidden="until-found" + beforematch)
            const supportsFocusGroup = 'focusGroup' in HTMLElement.prototype;
            document.querySelectorAll('[data-dx-tabs]').forEach((container) => {
                const tablist = container.querySelector('[data-dx-tablist]');
                if (!tablist) return;
                const tabs = [...tablist.querySelectorAll('button[data-dx-tab]')];
                const panels = [...container.querySelectorAll('[role="tabpanel"][data-dx-panel]')];
                if (!tabs.length || !panels.length) return;

                if (!supportsFocusGroup) {
                    tablist.setAttribute('role', 'tablist');
                    tabs.forEach((t) => t.setAttribute('role', 'tab'));
                }

                const selectTab = (targetTab, shouldFocus = false) => {
                    const targetKey = targetTab.dataset.dxTab;
                    tabs.forEach((t) => {
                        const active = t === targetTab;
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                        t.toggleAttribute('focusgroupstart', active);
                        if (!supportsFocusGroup) {
                            t.setAttribute('tabindex', active ? '0' : '-1');
                        }
                    });
                    panels.forEach((p) => {
                        const active = p.dataset.dxPanel === targetKey;
                        if (active) {
                            p.removeAttribute('hidden');
                        } else {
                            p.setAttribute('hidden', 'until-found');
                        }
                    });
                    targetTab.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                    if (shouldFocus) targetTab.focus();
                    container.dispatchEvent(new CustomEvent('dx:tab-changed', { detail: { tab: targetKey } }));
                };

                const initialTab = tabs.find((t) => t.getAttribute('aria-selected') === 'true') || tabs[0];
                if (!supportsFocusGroup) {
                    tabs.forEach((t) => t.setAttribute('tabindex', t === initialTab ? '0' : '-1'));
                }

                tabs.forEach((tab) => {
                    tab.addEventListener('click', () => selectTab(tab, false));
                });

                panels.forEach((panel) => {
                    panel.addEventListener('beforematch', () => {
                        const matchTab = tabs.find((t) => t.dataset.dxTab === panel.dataset.dxPanel);
                        if (matchTab) selectTab(matchTab, false);
                    });
                });

                if (!supportsFocusGroup) {
                    tablist.addEventListener('keydown', (e) => {
                        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
                        const idx = tabs.indexOf(document.activeElement);
                        if (idx < 0) return;
                        e.preventDefault();
                        let nextIdx = idx;
                        if (e.key === 'ArrowRight') nextIdx = (idx + 1) % tabs.length;
                        if (e.key === 'ArrowLeft') nextIdx = (idx - 1 + tabs.length) % tabs.length;
                        if (e.key === 'Home') nextIdx = 0;
                        if (e.key === 'End') nextIdx = tabs.length - 1;
                        selectTab(tabs[nextIdx], true);
                    });
                }
            });

            // 6. Accessible Popover Menus (aria-expanded sync + anchor fallback + focusgroup keyboard fallback)
            const supportsAnchor = CSS.supports('position-area', 'block-end');
            document.querySelectorAll('[popover].dx-menu-popover').forEach((popoverEl) => {
                const trigger = document.querySelector(`[popovertarget="${popoverEl.id}"]`);
                const group = popoverEl.querySelector('[focusgroup~="menu"]');

                if (group && !supportsFocusGroup) {
                    group.setAttribute('role', 'menu');
                    group.querySelectorAll('a, button').forEach((item) => {
                        if (!item.getAttribute('role')) item.setAttribute('role', 'menuitem');
                    });
                    group.addEventListener('keydown', (e) => {
                        if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(e.key)) return;
                        const items = [...group.querySelectorAll('a, button')].filter((i) => !i.disabled);
                        const idx = items.indexOf(document.activeElement);
                        if (idx < 0 || !items.length) return;
                        e.preventDefault();
                        const next = e.key === 'Home' ? 0 : e.key === 'End' ? items.length - 1 :
                            (idx + (e.key === 'ArrowUp' ? -1 : 1) + items.length) % items.length;
                        items[next].focus({ preventScroll: true });
                    });
                }

                if (trigger) {
                    trigger.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            if (!popoverEl.matches(':popover-open')) popoverEl.showPopover();
                            const firstItem = popoverEl.querySelector('a, button');
                            if (firstItem) firstItem.focus();
                        }
                    });
                }

                popoverEl.addEventListener('toggle', (e) => {
                    const isOpen = e.newState === 'open';
                    if (trigger) trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    if (isOpen && trigger && !supportsAnchor) {
                        const rect = trigger.getBoundingClientRect();
                        const menuRect = popoverEl.getBoundingClientRect();
                        let top = rect.bottom + 8;
                        if (top + menuRect.height > window.innerHeight - 12) {
                            top = Math.max(12, rect.top - menuRect.height - 8);
                        }
                        let left = popoverEl.classList.contains('dx-popover-start')
                            ? rect.left
                            : rect.right - menuRect.width;
                        left = Math.max(12, Math.min(left, window.innerWidth - menuRect.width - 12));
                        popoverEl.style.top = `${top}px`;
                        popoverEl.style.left = `${left}px`;
                    }
                });
            });
        })();
    </script>
