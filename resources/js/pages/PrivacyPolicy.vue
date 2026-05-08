<script lang="ts" setup></script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <div class="mx-auto max-w-3xl px-6 py-16">
            <!-- Header -->
            <div class="mb-12">
                <p class="mb-2 text-sm font-medium tracking-widest text-primary uppercase">Afor</p>
                <h1 class="mb-3 text-4xl font-bold text-foreground">Privacy Policy</h1>
                <p class="text-sm text-muted-foreground">Effective Date: May 4, 2026</p>
                <div class="mt-6 h-px bg-border" />
            </div>

            <!-- Intro -->
            <p class="mb-10 leading-relaxed text-muted-foreground">
                At Afor, we take your privacy seriously. This Privacy Policy explains how we collect, use, disclose, and
                safeguard your information when you use the Afor web application — a Kanban-based project management and
                collaboration tool. By using Afor, you agree to the terms described in this policy.
            </p>

            <!-- Sections -->
            <div class="space-y-10">
                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">1.</span> Information We Collect
                    </h2>
                    <p class="mb-3 leading-relaxed text-muted-foreground">
                        We collect the following personal information when you register and use Afor:
                    </p>
                    <ul class="space-y-2 text-muted-foreground">
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Name</strong> — used to identify you within the app and
                                to other members of your workspace.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Email Address</strong> — used for account creation,
                                login, workspace invitations, and communications.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Password</strong> — stored in a hashed and salted
                                format. We do not store plaintext passwords.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Profile Picture</strong> (optional) — uploaded images
                                are stored securely via Cloudflare R2. Future updates may also allow file attachments to
                                cards.</span
                            >
                        </li>
                    </ul>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground italic">
                        We do not collect sensitive personal information such as financial data, government IDs, or
                        health information.
                    </p>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">2.</span> How We Use Your Information
                    </h2>
                    <p class="mb-3 leading-relaxed text-muted-foreground">
                        Your information is used solely to provide and improve the Afor service:
                    </p>
                    <ul class="space-y-2 text-muted-foreground">
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span>To authenticate you and manage your session securely.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                >To display your name and profile picture to collaborators in shared workspaces.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span>To facilitate workspace invitations using your email address.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span>To remember your preferences (e.g., theme and sidebar state) across sessions.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span>To monitor general site usage and performance via Cloudflare analytics.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span>To respond to your inquiries or support requests.</span>
                        </li>
                    </ul>
                    <p class="mt-4 text-sm leading-relaxed font-medium text-foreground text-muted-foreground">
                        We do not sell, rent, or share your personal information with third parties for their marketing
                        purposes.
                    </p>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">3.</span> Cookies and Similar Technologies
                    </h2>
                    <p class="mb-4 leading-relaxed text-muted-foreground">
                        Afor uses cookies to enhance your experience and maintain session state:
                    </p>
                    <div class="overflow-hidden rounded-lg border border-border">
                        <div class="grid grid-cols-[1fr_2fr] text-sm">
                            <div class="border-b border-border bg-muted px-4 py-2 font-medium text-foreground">
                                Cookie
                            </div>
                            <div class="border-b border-border bg-muted px-4 py-2 font-medium text-foreground">
                                Purpose
                            </div>

                            <div class="border-b border-border px-4 py-3 font-mono text-xs text-primary">session</div>
                            <div class="border-b border-border px-4 py-3 text-muted-foreground">
                                Maintains your authenticated session after login.
                                <span class="font-medium text-foreground">Strictly necessary.</span>
                            </div>

                            <div class="border-b border-border px-4 py-3 font-mono text-xs text-primary">
                                xsrf-token
                            </div>
                            <div class="border-b border-border px-4 py-3 text-muted-foreground">
                                Protects against Cross-Site Request Forgery (CSRF) attacks.
                                <span class="font-medium text-foreground">Strictly necessary.</span>
                            </div>

                            <div class="border-b border-border px-4 py-3 font-mono text-xs text-primary">theme</div>
                            <div class="border-b border-border px-4 py-3 text-muted-foreground">
                                Stores your chosen display theme (light, dark, or system).
                                <span class="font-medium text-foreground">Functional.</span>
                            </div>

                            <div class="px-4 py-3 font-mono text-xs text-primary">sidebar</div>
                            <div class="px-4 py-3 text-muted-foreground">
                                Remembers whether the sidebar is open or closed.
                                <span class="font-medium text-foreground">Functional.</span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
                        Disabling session or XSRF cookies will prevent you from using the app. Theme and sidebar cookies
                        are non-essential and can be cleared without affecting core functionality.
                    </p>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">4.</span> Third-Party Services
                    </h2>
                    <p class="mb-3 leading-relaxed text-muted-foreground">
                        Afor uses the following third-party services that may process your data:
                    </p>
                    <ul class="space-y-2 text-muted-foreground">
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Cloudflare R2 (Storage)</strong> — profile pictures and
                                future card attachments are stored in Cloudflare R2 object storage. Cloudflare processes
                                data in accordance with their
                                <a
                                    class="text-primary underline underline-offset-2 transition-opacity hover:opacity-75"
                                    href="https://www.cloudflare.com/privacypolicy/"
                                    target="_blank"
                                    >privacy policy</a
                                >.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Cloudflare (Analytics & Security)</strong> — we use
                                Cloudflare for aggregated, anonymized visitor analytics and network security. Cloudflare
                                may collect technical data such as IP addresses.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Amazon Web Services RDS (Database)</strong> — user
                                account data (name, email, and hashed password) is stored in a managed relational
                                database hosted on AWS RDS in the ap-southeast-1 (Singapore) region. AWS processes data
                                in accordance with their
                                <a
                                    class="text-primary underline underline-offset-2 transition-opacity hover:opacity-75"
                                    href="aws.amazon.com/privacy"
                                    target="_blank"
                                    >privacy policy</a
                                >.
                            </span>
                        </li>
                    </ul>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
                        These services are used strictly for infrastructure, storage, and security. We do not use
                        advertising networks or behavioral tracking services.
                    </p>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">5.</span> Data Sharing and Disclosure
                    </h2>
                    <ul class="space-y-2 text-muted-foreground">
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Within Your Workspace</strong> — your name and profile
                                picture are visible to other members of workspaces you belong to or are invited
                                to.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Service Providers</strong> — we share data with
                                Cloudflare solely to operate the app's storage and infrastructure.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Legal Obligations</strong> — we may disclose
                                information if required by applicable Philippine law, regulation, or court order.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Business Transfers</strong> — in the event of a merger,
                                acquisition, or sale of assets, your information may be transferred as part of that
                                transaction.</span
                            >
                        </li>
                    </ul>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">6.</span> Data Retention
                    </h2>
                    <p class="leading-relaxed text-muted-foreground">
                        We retain your personal data for as long as your account is active or as necessary to provide
                        you with the Afor service. If you request deletion of your account, we will delete your personal
                        data within a reasonable time, except where we are required to retain it by law or for
                        legitimate business purposes.
                    </p>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">7.</span> Data Security
                    </h2>
                    <p class="mb-3 leading-relaxed text-muted-foreground">
                        We implement reasonable technical and organizational measures to protect your information:
                    </p>
                    <ul class="space-y-2 text-muted-foreground">
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span>Passwords are hashed and never stored in plaintext.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span>CSRF protection via the XSRF-Token cookie.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span>All data in transit is encrypted via HTTPS.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span>Uploaded files are stored securely via Cloudflare R2.</span>
                        </li>
                    </ul>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
                        No method of transmission over the Internet is 100% secure. While we strive to protect your
                        data, we cannot guarantee absolute security.
                    </p>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">8.</span> Your Rights
                    </h2>
                    <p class="mb-3 leading-relaxed text-muted-foreground">
                        Under the
                        <strong class="text-foreground">Republic Act No. 10173 (Data Privacy Act of 2012)</strong> of
                        the Philippines, you have the following rights:
                    </p>
                    <ul class="space-y-2 text-muted-foreground">
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Right to be Informed</strong> — know how your data is
                                collected and used.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Right to Access</strong> — request a copy of the
                                personal data we hold about you.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Right to Rectification</strong> — request correction of
                                inaccurate or incomplete data.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Right to Erasure</strong> — request deletion of your
                                personal data, subject to legal retention requirements.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Right to Data Portability</strong> — request your data
                                in a structured, commonly used format.</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 text-primary">•</span
                            ><span
                                ><strong class="text-foreground">Right to Object</strong> — object to the processing of
                                your data in certain circumstances.</span
                            >
                        </li>
                    </ul>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
                        To exercise any of these rights, contact us at
                        <a
                            class="text-primary underline underline-offset-2 transition-opacity hover:opacity-75"
                            href="mailto:afor@mandyllagas.com"
                            >afor@mandyllagas.com</a
                        >.
                    </p>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">9.</span> Children's Privacy
                    </h2>
                    <p class="leading-relaxed text-muted-foreground">
                        Afor is not directed at children under the age of 13. We do not knowingly collect personal
                        information from children. If you believe we have inadvertently collected data from a child,
                        please contact us immediately and we will take steps to delete it.
                    </p>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">10.</span> Changes to This Privacy Policy
                    </h2>
                    <p class="leading-relaxed text-muted-foreground">
                        We may update this Privacy Policy from time to time. When we do, we will revise the effective
                        date at the top of this page. Continued use of Afor after changes are posted constitutes your
                        acceptance of the updated policy.
                    </p>
                </section>

                <div class="h-px bg-border" />

                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <span class="text-primary">11.</span> Contact Us
                    </h2>
                    <p class="mb-3 leading-relaxed text-muted-foreground">
                        If you have questions, concerns, or requests regarding this Privacy Policy or your personal
                        data, please reach out:
                    </p>
                    <div class="inline-flex flex-col gap-1 rounded-lg border border-border bg-card px-5 py-4">
                        <a
                            class="font-medium text-primary transition-opacity hover:opacity-75"
                            href="mailto:afor@mandyllagas.com"
                            >afor@mandyllagas.com</a
                        >
                        <span class="text-sm text-muted-foreground">Philippines</span>
                    </div>
                </section>
            </div>

            <!-- Footer -->
            <div class="mt-16 border-t border-border pt-6 text-center">
                <p class="text-xs text-muted-foreground">
                    This Privacy Policy is governed by the laws of the Republic of the Philippines.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
