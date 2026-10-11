import React from "react";
import { CalendarDays, X } from "lucide-react";

export default function AuthModal({
  mode,
  form,
  onFormChange,
  onModeChange,
  onSubmit,
  onClose,
  onClearError,
}) {
  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div
        className="modal auth-modal"
        onClick={(click) => click.stopPropagation()}
      >
        <button className="modal-close" onClick={onClose}>
          <X />
        </button>
        <div className="auth-mark">
          <CalendarDays size={26} />
        </div>
        <h2>
          {mode === "login" ? "Welcome back." : "Join the campus community."}
        </h2>
        <p>
          {mode === "login"
            ? "Log in to save events and keep your campus life close."
            : "Create your CampusEvents Hub account."}
        </p>
        <form onSubmit={onSubmit}>
          {mode === "register" && (
            <label>
              Full name
              <input
                required
                value={form.name}
                onChange={(event) => onFormChange("name", event.target.value)}
                placeholder="Your name"
              />
            </label>
          )}
          <label>
            Email address
            <input
              required
              type="email"
              value={form.email}
              onChange={(event) => onFormChange("email", event.target.value)}
              placeholder="you@spc.edu.ph"
            />
          </label>
          <label>
            Password
            <input
              required
              type="password"
              minLength="8"
              value={form.password}
              onChange={(event) => onFormChange("password", event.target.value)}
              placeholder="At least 8 characters"
            />
          </label>
          {mode === "register" && (
            <label>
              Confirm password
              <input
                required
                type="password"
                value={form.password_confirmation}
                onChange={(event) =>
                  onFormChange("password_confirmation", event.target.value)
                }
                placeholder="Repeat password"
              />
            </label>
          )}
          <button className="btn btn-primary full-btn" type="submit">
            {mode === "login" ? "Log in" : "Create account"}
          </button>
        </form>
        <div className="auth-switch">
          {mode === "login"
            ? "New to CampusEvents Hub?"
            : "Already have an account?"}{" "}
          <button
            onClick={() => {
              onModeChange(mode === "login" ? "register" : "login");
              onClearError();
            }}
          >
            {mode === "login" ? "Sign up" : "Log in"}
          </button>
        </div>
      </div>
    </div>
  );
}
