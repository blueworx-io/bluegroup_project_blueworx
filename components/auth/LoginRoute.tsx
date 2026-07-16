"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { AuthProvider } from "@/lib/auth/AuthProvider";
import SignInForm from "@/components/auth/SignInForm";

export default function LoginRoute() {
  const router = useRouter();
  const next = useSearchParams().get("next") || "/portal";
  return (
    <AuthProvider requireAuth>
      <main className="auth-page">
        <SignInForm onSuccess={() => router.push(next)} />
      </main>
    </AuthProvider>
  );
}
