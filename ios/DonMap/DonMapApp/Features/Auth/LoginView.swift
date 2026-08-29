import SwiftUI
import DonMapCore

struct LoginView: View {
    let authRepository: AuthRepository
    @State private var email = ""
    @State private var password = ""
    @State private var error: String?

    var body: some View {
        Form {
            TextField("auth_email", text: $email)
                .textContentType(.emailAddress)
                .keyboardType(.emailAddress)
            SecureField("auth_password", text: $password)
            if let error {
                Text(error).foregroundStyle(.red)
            }
            Button("auth_submit") {
                Task { await submit() }
            }
        }
        .navigationTitle("auth_login_title")
    }

    private func submit() async {
        do {
            try await authRepository.login(email: email, password: password)
            error = nil
        } catch {
            self.error = NSLocalizedString("auth_error", comment: "")
        }
    }
}
