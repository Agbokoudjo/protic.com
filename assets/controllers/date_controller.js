import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    date: String,
    locale: { type: String, default: undefined },
  }

    connect() {
        if (!this.dateValue) return;
        
    const date = new Date(this.dateValue);
    // Si la locale du user est fournie, on l’utilise, sinon fallback = locale navigateur
    const locale = this.localeValue || undefined;
    const formatter = new Intl.DateTimeFormat(locale, {
      dateStyle: "medium",
      timeStyle: "short",
    });

      this.element.textContent = formatter.format(date);
  }
}
