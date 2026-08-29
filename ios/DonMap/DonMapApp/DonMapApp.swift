import SwiftUI

@main
struct DonMapApp: App {
    private let apiClient = ApiClient(baseURL: URL(string: "http://localhost:8080")!)
    private var listingsRepository: ListingsRepository { ListingsRepository(apiClient: apiClient) }

    var body: some Scene {
        WindowGroup {
            ContentView(listingsRepository: listingsRepository)
        }
    }
}
