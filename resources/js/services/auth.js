export const Auth = {
  user: window._USER,

  hasRole(role) {
    return this.user?.roles?.includes(role);
  },

  hasPermission(permission) {
    return this.user?.permissions?.includes(permission);
  },

  isLoggedIn() {
    return !!this.user;
  }
};
