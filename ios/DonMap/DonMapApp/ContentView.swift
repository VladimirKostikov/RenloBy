import SwiftUI
import DonMapCore

struct ContentView: View {
    let listingsRepository: ListingsRepository
    @State private var query = ""
    @State private var items: [ListingItem] = []
    @State private var loading = true

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                searchField
                HStack(spacing: 0) {
                    listingsPanel
                    mapPanel
                }
            }
            .navigationTitle("app_name")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItemGroup(placement: .topBarTrailing) {
                    Button("nav_sale") {}
                    Button("nav_rent") {}
                    Button("nav_favorites") {}
                }
            }
        }
        .task(id: query) {
            await loadListings()
        }
    }

    private var searchField: some View {
        TextField("search_placeholder", text: $query)
            .textFieldStyle(.roundedBorder)
            .padding()
    }

    private var listingsPanel: some View {
        Group {
            if loading {
                ProgressView("listing_loading")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if items.isEmpty {
                Text("listing_no_results")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else {
                List(items) { item in
                    VStack(alignment: .leading, spacing: 4) {
                        Text(item.title).font(.headline)
                        Text(item.price).font(.subheadline)
                        Text("\(item.rooms) комн. · \(item.area, specifier: "%.0f") м²")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                    .padding(.vertical, 4)
                }
                .listStyle(.plain)
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
    }

    private var mapPanel: some View {
        VStack(spacing: 8) {
            Text("map_placeholder").font(.title3)
            Text("map_hint").font(.caption).foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(Color(.systemGroupedBackground))
    }

    private func loadListings() async {
        loading = true
        defer { loading = false }
        items = (try? await listingsRepository.search(query: query)) ?? []
    }
}

#Preview {
    ContentView(listingsRepository: ListingsRepository(apiClient: ApiClient(baseURL: URL(string: "http://localhost:8080")!)))
}
