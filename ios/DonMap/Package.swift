// swift-tools-version: 5.9
import PackageDescription

let package = Package(
    name: "DonMapCore",
    platforms: [.iOS(.v16)],
    products: [
        .library(name: "DonMapCore", targets: ["DonMapCore"]),
    ],
    targets: [
        .target(name: "DonMapCore", path: "Sources/DonMapCore"),
    ]
)
