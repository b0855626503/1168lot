<template>
  <nav id="main-nav" class="navbar navbar-expand-sm navbar-light">
    <div class="container" style="max-height: 100%">
      <!-- Burger menu -->
      <div class="d-inline-flex align-items-center ham-menu">
        <button
            :aria-expanded="isNavOpen.toString()"
            aria-controls="navbarSupportedContent"
            aria-label="Toggle navigation"
            class="navbar-toggler p-0"
            style="height: 35px; width: 35px"
            type="button"
            @click="toggleNavbar"
        >
          <span class="bi bi-list bi-2x text-light"></span>
        </button>
      </div>

      <!-- Logo -->
      <a
          :href="routes.sessionIndex"
          class="navbar-brand m-0 d-flex align-items-center not-login-stay-left"
      >
        <img id="main-logo" :src="logoUrl"/>
      </a>

      <!-- Logout -->
      <div
          id="auth-wrapper"
          class="group-button-user p-1 rounded-pill login-b"
      >
        <div class="d-none d-md-inline-flex">
          <a
              :href="routes.sessionDestroy"
              class="nav-link register-btn btn btn-custom-secondary rounded-pill d-flex align-items-center pt-1 pb-1 text-white justify-content-center homeregis"
          >
                        <span
                            class="fw-bold text-highlight d-flex align-items-center"
                        >
                            <i
                                class="bi bi-box-arrow-right me-2 nav-icon text-white"
                            ></i>
                            {{ $t("app.home.logout") }}
                        </span>
          </a>
        </div>
      </div>

      <!-- Menu -->
      <div
          id="navbarSupportedContent"
          :class="{ show: isNavOpen }"
          class="collapse navbar-collapse navbar-content-index"
      >
        <div class="navbar-nav ms-auto align-items-center">
          <li class="nav-item header-group-menu pt-3">
            <span>Pages</span>
          </li>

          <li class="nav-item bg-box-1 nc-home btn-home">
            <a
                :href="routes.homeIndex"
                class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative"
            >
                            <span class="text-highlight">{{
                                $t("app.login.home")
                              }}</span>
            </a>
          </li>

          <li
              class="nav-item bg-box-1 line__ti_p_390ypsoj btn-contact"
          >
            <a
                :href="config.linelink"
                class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative"
                target="_blank"
            >
                            <span class="text-highlight">{{
                                $t("app.home.contact")
                              }}</span>
            </a>
          </li>

          <li
              class="nav-item bg-box-1 w-100 d-flex justify-content-center btn-deposit"
          >
            <button
                class="btn nav-link custom btn-box-1 d-flex align-items-center btn-lg"
                type="button"
                @click="$emit('open-deposit')"
            >
                            <span class="nav-item-text text-highlight">{{
                                $t("app.home.deposit")
                              }}</span>
            </button>
          </li>

          <li
              class="nav-item bg-box-1 w-100 d-flex justify-content-center flex-lg-fill btn-withdraw"
          >
            <button
                class="btn nav-link custom btn-box-1 d-flex align-items-center btn-lg"
                type="button"
                @click="$emit('open-withdraw')"
            >
                            <span class="nav-item-text text-highlight">{{
                                $t("app.home.withdraw")
                              }}</span>
            </button>
          </li>

          <li class="nav-item header-group-menu pt-3">
            <span>Games</span>
          </li>

          <li
              v-for="menu in menus"
              :key="menu.key"
              class="nav-item cutom-game-entry d-flex justify-content-center invert-color position-relative"
              @click="$emit('select-tab', menu.key)"
          >
            <a
                class="nav-link btn btn-box-1 d-flex align-items-center btn-lg"
                href="javascript:void(0)"
            >
                            <span class="nav-item-text text-highlight">{{
                                $t("app.home." + menu.key)
                              }}</span>
            </a>
          </li>

          <li
              class="nav-item bg-box-2 d-inline-flex ms-2 logout-mobile"
          >
            <button
                class="border-0 text-decoration-none shadow px-3 btn-custom-secondary rounded-pill d-flex justify-content-center align-items-center nav-link btn btn-box-2"
                type="button"
                @click="redirectTo('/')"
            >
              <i
                  class="bi bi-box-arrow-right me-1 nav-icon pe-1"
                  style="color: rgb(181, 60, 60) !important"
              ></i>
              <span
                  style="
                                    color: rgb(244, 170, 170) !important;
                                    padding-left: 0 !important;
                                "
              >{{ $t("app.home.logout") }}</span
              >
            </button>
          </li>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  name: "Navbar",
  props: {
    routes: Object,
    config: Object,
    menus: Array,
    logoUrl: String,
  },
  data() {
    return {
      isNavOpen: false,
    };
  },
  methods: {
    toggleNavbar() {
      this.isNavOpen = !this.isNavOpen;
    },
    redirectTo(path) {
      window.location.href = path;
    },
  },
};
</script>
